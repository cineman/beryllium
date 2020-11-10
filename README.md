# beryllium

A redis based queuing system for PHP.

This system is not build specifically for performance, adding jobs to the queue introduces alot of overhead. The system is meant for background processing and big expansive tasks that can run in parallel. 

## Linux Service

Create a system service file under `/etc/systemd/system/beryllium.service`

```
[Unit]
Description=Beryllium Server
After=network.target
After=systemd-user-sessions.service
After=network-online.target

[Service]
ExecStart=/your/path/to/your/beryllium/pm
TimeoutSec=30
Restart=on-failure
RestartSec=30
StartLimitInterval=350
StartLimitBurst=10

[Install]
WantedBy=multi-user.target
```

