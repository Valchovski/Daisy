using System;
using System.Collections.Generic;
using System.IO;
using System.IO.Ports;
using System.Linq;
using System.Net;
using System.Reflection;
using System.Runtime.Remoting;
using System.Text;
using System.Threading.Tasks;
using DotLiquid;
using log4net;
using log4net.Util;
using NHttp;

namespace CRServer.Administration
{
    class AdminServer
    {
        private static readonly ILog log = LogManager.GetLogger(typeof (AdminServer));
        private readonly Dictionary<string, Action<HttpRequestEventArgs>> mappedUrls = new Dictionary<string, Action<HttpRequestEventArgs>>(); 
        private HttpServer server;
        private readonly List<string> errors = new List<string>();
        private readonly List<string> success = new List<string>();

        internal void Init()
        {
            log.Info("Initializing administration interface");
            server = new HttpServer();
            server.ServerBanner = "Cash Register administration server";
            server.EndPoint = new IPEndPoint(IPAddress.Any, CashRegisterServer.Instance.Configuration.AdminstrationPort);
            server.RequestReceived += OnServerRequest;
            mappedUrls.Add("/saveGeneral", saveGeneral);
            mappedUrls.Add("/saveSerial", saveSerial);
            mappedUrls.Add("/delete", deleteIP);
            mappedUrls.Add("/add", addIP);
        }

        internal void Start()
        {
            log.Info("Starting administration interface service");
            server.Start();
        }

        internal void Stop()
        {
            log.Info("Stopping administration interface service");
            server.Stop();
        }
        
        private void OnServerRequest(object sender, HttpRequestEventArgs e)
        {
            if (!Authorize(e)) return;
            if (mappedUrls.ContainsKey(e.Request.Url.AbsolutePath))
            {
                mappedUrls[e.Request.Url.AbsolutePath](e);
                return;
            }
            string page = e.Request.Url.AbsolutePath.Substring(1);
            string type = "text/html";
            if (page.Length == 0)
                page = "index.html";
            page = page.Replace("/", ".");
            if (page.StartsWith("css."))
                type = "text/css";
            else if (page.StartsWith("js."))
                type = "application/javascript";
            e.Response.ContentType = type;
            page = $"CRServer.Administration.Data.{page}";
            Stream stream = GetType().Assembly.GetManifestResourceStream(page);
            if (stream == Stream.Null || stream == null)
            {
                notFound(e);
                return;
            }
            if (type == "text/html" && page.EndsWith(".html"))
                processPage(e, stream);
            else
                stream.CopyTo(e.Response.OutputStream);
        }

        private bool Authorize(HttpRequestEventArgs e)
        {
            Configuration cfg = CashRegisterServer.Instance.Configuration;
            if (!cfg.AllowedIpAddresses.Contains(e.Request.UserHostAddress))
            {
                e.Response.StatusCode = 403;
                using (StreamWriter w = new StreamWriter(e.Response.OutputStream))
                {
                    w.Write("403 - Access denied");
                }
                return false;
            }
            return true;
        }

        private void notFound(HttpRequestEventArgs e)
        {
            e.Response.Status = "Requested destination was not found";
            e.Response.StatusCode = 404;
            using (var writer = new StreamWriter(e.Response.OutputStream))
            {
                writer.Write("404 - Missing or invalid resource");
            }
        }

        private void processPage(HttpRequestEventArgs e, Stream data)
        {
            string page = "";
            using (StreamReader sr = new StreamReader(data))
                page = sr.ReadToEnd();
            Template t = Template.Parse(page);
            var p = new RenderParameters() { LocalVariables = new Hash() };
            var obj = CashRegisterServer.Instance.Configuration.ToLiquid();
            p.LocalVariables.Add("config", obj);
            p.LocalVariables.Add("errors", errors);
            p.LocalVariables.Add("success", success);
            t.Render(e.Response.OutputStream, p);
            errors.Clear();
            success.Clear();
        }

        private void saveGeneral(HttpRequestEventArgs e)
        {
            if( string.IsNullOrEmpty(e.Request.Form["admin_port"]) )
                errors.Add("Administration port cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["service_port"]))
                errors.Add("Service port cannot be empty");

            ushort aport, sport;
            if (!ushort.TryParse(e.Request.Form["admin_port"], out aport))
                errors.Add("Invalid administration port - must be a number between 1 and 65000");
            if (!ushort.TryParse(e.Request.Form["service_port"], out sport))
                errors.Add("Invalid service port - must be a number between 1 and 65000");

            if (errors.Count == 0)
            {
                CashRegisterServer.Instance.Configuration.ServicePort = sport;
                CashRegisterServer.Instance.Configuration.AdminstrationPort = aport;
                CashRegisterServer.Instance.Configuration.Save();
                log.Info("General configuration saved successfully");
                success.Add("Configuration changes saved successfully");
                if (server.EndPoint.Port != aport)
                {
                    log.Info("Restarting administration server with new port");
                    server.Stop();
                    server.EndPoint = new IPEndPoint(IPAddress.Any, CashRegisterServer.Instance.Configuration.AdminstrationPort);
                    server.Start();
                    log.Info("Administration server running on port " + aport );
                }
                CashRegisterServer.Instance.SerialServer.PortChanged(sport);
            }
            else
                log.Error("General configuration error");

            e.Response.Redirect( "/index.html" );
        }

        private void saveSerial(HttpRequestEventArgs e)
        {
            if (string.IsNullOrEmpty(e.Request.Form["port"]))
                errors.Add("Serial port cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["baudrate"]))
                errors.Add("Baudrate cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["parity"]))
                errors.Add("Parity cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["databits"]))
                errors.Add("Data bits cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["stopbits"]))
                errors.Add("Stop bits cannot be empty");
            if (string.IsNullOrEmpty(e.Request.Form["packetlen"]))
                errors.Add("Packet len cannot be empty");

            ushort baudrate, timeout;
            byte parity, databits, stopbits, len;

            if (!ushort.TryParse(e.Request.Form["baudrate"], out baudrate))
                errors.Add("Invalid Baudrate - must be a number between 1 and 65000");
            if (!byte.TryParse(e.Request.Form["parity"], out parity))
                errors.Add("Invalid Parity - must be a number between 0 and 4");
            if (!byte.TryParse(e.Request.Form["databits"], out databits))
                errors.Add("Invalid Data Bits - must be a number between 5 and 9");
            if (!byte.TryParse(e.Request.Form["stopbits"], out stopbits))
                errors.Add("Invalid Stop Bits - must be a number between 0 and 3");
            if (!byte.TryParse(e.Request.Form["packetlen"], out len))
                errors.Add("Invalid Packet len - must be a number between 0 and 255");
            if (!ushort.TryParse(e.Request.Form["timeout"], out timeout))
                errors.Add("Invalid Timeout - must be a number between 0 and 65535");

            if (errors.Count == 0)
            {
                Configuration cfg = CashRegisterServer.Instance.Configuration;
                cfg.SerialPort = e.Request.Form["port"];
                cfg.Baudrate = baudrate;
                cfg.Parity = (Parity) parity;
                cfg.DataBits = databits;
                cfg.StopBits = (StopBits) stopbits;
                cfg.PacketLength = len;
                cfg.ReadTimeout = timeout;
                cfg.Save();
                log.Info("Serial port configuration saved successfully");
                success.Add("Serial port configuration saved successfully");
            }
            e.Response.Redirect("/serial.html");
        }

        private void deleteIP(HttpRequestEventArgs e)
        {
            string ip = e.Request.Params["ip"];
            if (string.IsNullOrEmpty(ip))
            {
                e.Response.Redirect("/access.html");
                return;
            }
            Configuration cfg = CashRegisterServer.Instance.Configuration;
            if (cfg.AllowedIpAddresses.Contains(ip))
            {
                cfg.AllowedIpAddresses.Remove(ip);
                cfg.Save();
                log.Info($"Access revoked for IP: {ip}");
            }
            e.Response.Redirect("/access.html");
        }

        private void addIP(HttpRequestEventArgs e)
        {
            string ip = e.Request.Form["ip"];
            if (string.IsNullOrEmpty(ip))
            {
                e.Response.Redirect("/access.html");
                return;
            }
            Configuration cfg = CashRegisterServer.Instance.Configuration;
            if (!cfg.AllowedIpAddresses.Contains(ip))
            {
                cfg.AllowedIpAddresses.Add(ip);
                cfg.Save();
                log.Info($"Access granted for IP: {ip}");
            }
            e.Response.Redirect("/access.html");
        }
    }
}
