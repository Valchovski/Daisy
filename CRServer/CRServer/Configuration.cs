using System;
using System.Collections.Generic;
using System.Dynamic;
using System.IO;
using System.IO.Ports;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using DotLiquid;
using log4net;
using Newtonsoft.Json;
using NHttp;

namespace CRServer
{
    public class Configuration : ILiquidizable
    {
        private static readonly ILog log = LogManager.GetLogger(typeof(Configuration));
        public ushort AdminstrationPort { get; set; } = 8081;
        public ushort ServicePort { get; set; } = 10101;
        public List<string> AllowedIpAddresses { get; set; } = new List<string>();
        public string SerialPort { get; set; } = "COM1";
        public int Baudrate { get; set; } = 9600;
        public Parity Parity { get; set; } = Parity.None;
        public int DataBits { get; set; } = 8;
        public StopBits StopBits { get; set; } = StopBits.One;
        public byte PacketLength { get; set; } = 17; // Daisy
        public ushort ReadTimeout { get; set; } = 100; // Msec 

        public byte ParityByte
        {
            get { return (byte) Parity; }
            set { Parity = (Parity) value; }
        }

        public byte StopBitsByte
        {
            get { return (byte) StopBits; }
            set { StopBits = (StopBits) value;  }
        }

        internal void Save()
        {
            log.Info("Saving configuration section");
            string config = JsonConvert.SerializeObject(this);
            File.WriteAllText("service.cfg", config);
        }

        internal void Load()
        {
            if (!File.Exists("service.cfg"))
            {
                log.Info("No saved configuration - using default values");
                AllowedIpAddresses.Add("127.0.0.1");
                DumpValues();
                return;
            }

            var cdata = File.ReadAllText("service.cfg");
            try
            {
                JsonConvert.PopulateObject(cdata, this);
            }
            catch (Exception e)
            {
                log.Error(e.Message);
            }
            DumpValues();
        }

        private void DumpValues()
        {
            log.Debug($"Administration port: {AdminstrationPort}");
            log.Debug($"ServicePort port: {ServicePort}");
            log.Debug($"Serial port: {SerialPort}");
            log.Debug($"Baudrate: {Baudrate}");
            log.Debug($"Parity: {Parity}");
            log.Debug($"DataBits: {DataBits}");
            log.Debug($"StopBits: {StopBits}");
            log.Debug($"Packet len: {PacketLength}");
            log.Debug($"Read Timeout: {ReadTimeout} milliseconds");
            log.Debug("Allowed IP addresses:");
            if( AllowedIpAddresses.Count == 0 )
                AllowedIpAddresses.Add("127.0.0.1");
            foreach (var addr in AllowedIpAddresses)
                log.Debug($"\t- {addr}");
        }

        public object ToLiquid()
        {
            return new {
                // General Config
                AdminstrationPort,
                ServicePort,

                // Serial Port Config
                SerialPorts = System.IO.Ports.SerialPort.GetPortNames(),
                SerialPort,
                Baudrate,
                ParityByte,
                DataBits,
                StopBitsByte,
                PacketLength,
                ReadTimeout,

                // Access Config
                AllowedIpAddresses
            };
        }
    }
}
