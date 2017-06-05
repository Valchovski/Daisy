using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.IO.Ports;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using log4net;
using Newtonsoft.Json;
using NHttp;

namespace CRServer
{
    class SerialServer
    {
        public class ResponseResult
        {
            public string Error { get; set; }
            public string Response { get; set; }
        }
        private static readonly ILog log = LogManager.GetLogger(typeof (SerialServer));
        private HttpServer server;

        internal void Init()
        {
            log.Info("Initializing main service");
            server = new HttpServer();
            server.EndPoint = new IPEndPoint(IPAddress.Any, CashRegisterServer.Instance.Configuration.ServicePort);
            server.ServerBanner = "Cash Regsiter Service";
            server.RequestReceived += OnRequest;
        }

        internal void Start()
        {
            log.Info("Starting main service");
            server.Start();
        }

        internal void Stop()
        {
            log.Info("Stopping main service");
            server.Stop();
        }

        private void OnRequest(object sender, HttpRequestEventArgs e)
        {
            if (!Authorize(e)) return;
            log.Debug("Service request: " + e.Request.Url.AbsolutePath);
            if (e.Request.InputStream == null)
            {
                using (StreamWriter w = new StreamWriter(e.Response.OutputStream))
                {
                    w.Write(JsonConvert.SerializeObject(new ResponseResult() {Error = "No data - empty request?"}));
                }
            }
            else
            {
                byte[] packet = new byte[e.Request.InputStream.Length];
                e.Request.InputStream.Read(packet, 0, packet.Length);
                log.Debug("Incomming packet: [ " + Print(packet) + " ]");
                Configuration cfg = CashRegisterServer.Instance.Configuration;
                SerialPort com = null;
                try
                {
                    com = new SerialPort(cfg.SerialPort, cfg.Baudrate, cfg.Parity, cfg.DataBits, cfg.StopBits);
                    com.Open();
                    com.Write(packet, 0, packet.Length);
                }
                catch (Exception ex)
                {
                    sendError(ex.Message, e);
                    return;
                }
                packet = new byte[10240]; // Max 10k packet
                int len = 0;
                DateTime start = DateTime.Now;
                com.ReadTimeout = 50 + cfg.ReadTimeout; // 50 + timeout 
                while (len < cfg.PacketLength )
                {
                    try
                    {
                        len += com.Read(packet, len, packet.Length - len);
                        start = DateTime.Now;
                    } catch( TimeoutException ) { /* ignore */ }
                    if (( DateTime.Now - start ).TotalMilliseconds >= cfg.ReadTimeout)
                        break;
                }
                try { com.Close(); }
                catch { /* ignored */ }
                if (len == 0)
                {
                    sendError($"No data received from device within {cfg.ReadTimeout} milliseconds", e );
                    return;
                }
                byte[] data = new byte[len];
                Array.Copy(packet, 0, data, 0, len);
                sendData(data, e);
            }
        }

        private void sendError(string error, HttpRequestEventArgs e)
        {
            log.Error(error);
            using (StreamWriter w = new StreamWriter(e.Response.OutputStream))
            {
                w.Write(JsonConvert.SerializeObject(new ResponseResult() { Error = error }));
            }
        }

        private void sendData(byte[] data, HttpRequestEventArgs e)
        {
            StringBuilder sb = new StringBuilder();
            foreach (var b in data)
                sb.AppendFormat(" 0x{0:X2}", b);
            log.Debug($"Sending data packet: [{sb} ]");
            using (StreamWriter w = new StreamWriter(e.Response.OutputStream))
            {
                w.Write(JsonConvert.SerializeObject(new ResponseResult() {Error = null, Response = Convert.ToBase64String(data)}));
            }
        }

        private string Print(byte[] data)
        {
            StringBuilder sb = new StringBuilder();
            foreach (var b in data)
                sb.AppendFormat(" 0x{0:X2}", b);
            return sb.ToString().Trim();
        }

        private bool Authorize(HttpRequestEventArgs e)
        {
            Configuration cfg = CashRegisterServer.Instance.Configuration;
            if (!cfg.AllowedIpAddresses.Contains(e.Request.UserHostAddress))
            {
                e.Response.StatusCode = 403;
                using (StreamWriter w = new StreamWriter(e.Response.OutputStream))
                {
                    w.Write(JsonConvert.SerializeObject(new { error = "Access denied" }));
                }
                return false;
            }
            return true;
        }

        public void PortChanged(ushort sport)
        {
            if (server.EndPoint.Port == sport) return;
            log.Info("Restarting serial server for port change");
            server.Stop();
            server.EndPoint = new IPEndPoint(IPAddress.Any, sport);
            server.Start();
            log.Info("Serial server is now working on port " + sport);
        }

        /*private static byte cmd_seq = 0x20;
        private void executeDemoCommand()
        {
            Configuration cfg = CashRegisterServer.Instance.Configuration;
            SerialPort com = new SerialPort(cfg.SerialPort, cfg.Baudrate, cfg.Parity, cfg.DataBits, cfg.StopBits);
            log.Debug("Opening Serial port: " + cfg.SerialPort);
            try
            {
                com.Open();
            }
            catch (Exception e)
            {
                log.Error(e.ToString());
                return;
            }
            if (cmd_seq < 0x20)
                cmd_seq = 0x20;
            byte[] buffer = new byte[250];
            int len = 0;
            buffer[len++] = 0x01;
            buffer[len++] = (byte) ( 0x25 );
            buffer[len++] = cmd_seq++;
            buffer[len++] = 0x2C;
            buffer[len++] = (byte)'5';
            buffer[len++] = 5;

            int sum = 0;
            for (int i = 1; i < len; i++)
                sum += buffer[i];
            string bcc = string.Format("{0:X4}", sum);
            byte[] bccBytes = new byte[4];
            for (int i = 0; i < 4; i++)
                bccBytes[i] = "ABCDEF".Contains(bcc[i]) ? Convert.ToByte("3" + bcc[i], 16) : (byte) bcc[i];
            Array.Copy(bccBytes, 0, buffer, len, 4);
            len += 4;
            buffer[len++] = 0x03;

            StringBuilder sb = new StringBuilder();
            sb.Append("Message [");
            for (int i = 0; i < len; i++)
                sb.AppendFormat(" 0x{0:X2}", buffer[i]);
            sb.Append("] ");
            log.Debug(sb.ToString());

            com.Write(buffer, 0, len);
            Thread.Sleep(10);
            len = 0;
            while (len < 17 )
            {
                len += com.Read(buffer, len, buffer.Length - len );
                if (len == 1)
                {
                    if (buffer[0] == 0x15)
                    {
                        cmd_seq--;
                        log.Error("Packet sent to device is invalid");
                        com.Close();
                        return;
                    }
                    else if (buffer[0] == 0x16) // Processing
                    {
                        len = 0;
                        continue;
                    }
                }
            }
            sb.Clear();
            for (int i = 0; i < len; i++)
                sb.AppendFormat(" 0x{0:X2}", buffer[i]);
            log.Debug("Perfect: [ " + sb.ToString() + " ]");

            com.Close();
        }*/
    }
}
