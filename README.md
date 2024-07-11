# Beryllium

![Hydrogen PHPUnit](https://github.com/cineman/beryllium/workflows/Hydrogen%20PHPUnit/badge.svg)

A Redis based queuing system for PHP.

## Performance

This system is not built specifically for performance, adding jobs to the queue introduces a lot of overhead. The system is meant for background processing and big expansive tasks that can run in parallel. 

Below is the output of the benchmarking script under `bin/benchmark` which compares serial vs parallel performance gain for an expansive task. 

The results are from my 2015 MacBook with an I7 quad-core CPU.

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
waiting for the queue result...
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

### Code Quality

Make sure the latest quality standards are met by executing the `phpcs` and `phpstan` scripts. There are three commands available which are defined in the root `composer.json` as custom scripts.

Execute `phpcs` and `phpcbf` for linting and automatic fixing respectively:

```
composer run-script ci-phpcs
composer run-script ci-phpcs-fix
```

Execute `phpstan` to analyse the code to detect code issues.

```
composer run-script ci-phpstan
```

### Tests

There are tests as part of this package in order to verify that everything works as expected. 

Execute the following command to run the tests:

```
composer run-script ci-phpunit
```

**Note: You need to configure your database connection first in `phpunit.xml` before running the tests. If `phpunit.xml` does not exist, copy the `phpunit.xml.dist` file.**