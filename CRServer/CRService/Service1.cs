using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Linq;
using System.ServiceProcess;
using System.Text;
using System.Threading.Tasks;
using CRServer;

namespace CRService
{
    partial class Service1 : ServiceBase
    {
        private CashRegisterServer server;

        public Service1()
        {
            InitializeComponent();
        }

        protected override void OnStart(string[] args)
        {
            server = new CashRegisterServer(false);
        }

        protected override void OnStop()
        {
            server.Stop();
        }
    }
}
