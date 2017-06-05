using System;
using System.Collections.Generic;
using System.IO;
using System.IO.Ports;
using System.Linq;
using System.Runtime.Remoting.Messaging;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using CRServer.Administration;
using log4net;
using log4net.Config;

namespace CRServer
{
    public class CashRegisterServer
    {
        private static readonly ILog log = LogManager.GetLogger(typeof (CashRegisterServer));
        public static CashRegisterServer Instance { get; private set; }
        internal Configuration Configuration { get; set; }
        internal AdminServer AdminServer { get; set; }
        internal SerialServer SerialServer { get; set; }
        internal bool doWork { get; set; } = true;

        public CashRegisterServer( bool lock_thread = true )
        {
            Instance = this;
            if( File.Exists("log.xml") )
                XmlConfigurator.Configure(new FileInfo("log.xml"));
            var ver = GetType().Assembly.GetName().Version;
            log.Info($"Initializing cash register server v{ver.Major}.{ver.Minor}  build: {ver.Build}, rev: {ver.Revision}");
            Configuration = new Configuration();
            log.Info("Loading service configuration");
            Configuration.Load();
            AdminServer = new AdminServer();
            AdminServer.Init();
            SerialServer = new SerialServer();
            SerialServer.Init();
            
            AdminServer.Start();
            SerialServer.Start();

            if (lock_thread)
            {
                while (doWork)
                {
                    Thread.Sleep(100);
                }
                AdminServer.Stop();
                SerialServer.Stop();
            }
        }

        public void Stop()
        {
            AdminServer.Stop();
            SerialServer.Stop();
        }
    }
}
