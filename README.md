# Beryllium

![Hydrogen PHPUnit](https://github.com/cineman/beryllium/workflows/Hydrogen%20PHPUnit/badge.svg)

A redis based queuing system for PHP.

## Performance

This system is not build specifically for performance, adding jobs to the queue introduces alot of overhead. The system is meant for background processing and big expansive tasks that can run in parallel. 

Below is the output of the benchmarking script under `bin/benchmark` which compares serial vs parrallel performance gain for an expansive task. 

The results are from my 2015 macbook with an I7 quad core CPU.

```
$ ./bin/benchmark
Running Benchmark.
Difficulty: 5

test.0: 731462
test.1: 1388492
test.2: 875977
test.3: 1671007
test.4: 2246545
test.5: 354618
test.6: 149774
test.7: 357441
test.8: 295073
test.9: 721618
test.10: 830209
test.11: 388492
test.12: 613212
test.13: 530877
test.14: 3049458
test.15: 110400
test.16: 1462453
test.17: 468794
test.18: 270536
test.19: 797613
test.20: 171398
test.21: 859504
test.22: 158738
test.23: 178193
test.24: 1263135

Serial test took about 6.048s

Now using the queue...
waiting for queue result...
test.0: 731462
test.1: 1388492
test.10: 830209
test.11: 388492
test.12: 613212
test.13: 530877
test.14: 3049458
test.15: 110400
test.16: 1462453
test.17: 468794
test.18: 270536
test.19: 797613
test.2: 875977
test.20: 171398
test.21: 859504
test.22: 158738
test.23: 178193
test.24: 1263135
test.3: 1671007
test.4: 2246545
test.5: 354618
test.6: 149774
test.7: 357441
test.8: 295073
test.9: 721618

Queue test took about 1.968s

Serial took 4.08s longer than the queue.
Queue was about 148.24% faster.
```

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

