# beryllium

A redis based queuing system for PHP.

## Linux Service

Create a system service file under `/etc/systemd/system/beryllium.service`

```
[Unit]
Description=Beryllium Server
After=network.target
After=systemd-user-sessions.service
After=network-online.target

[Service]
ExecStart=/your/path/to/beryllium/process-manager
TimeoutSec=30
Restart=on-failure
RestartSec=30
StartLimitInterval=350
StartLimitBurst=10

[Install]
WantedBy=multi-user.target
```

