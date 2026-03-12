

# Monitoramento de CPU – Servidor Ubuntu com Docker (Frigate)

## Servidor

10.0.0.139/var/www/html/monitor-cpu

## Objetivo

Implementar um sistema de monitoramento técnico para o servidor responsável pela execução do ambiente **Sentinela** e **Docker**, permitindo:

* detecção rápida de sobrecarga
* monitoramento visual em tempo real
* histórico de desempenho do servidor
* análise técnica após travamentos
* acompanhamento de containers Docker

O sistema combina duas camadas de monitoramento:

**1️⃣ Monitoramento em tempo real (Dashboard Web)**
**2️⃣ Monitoramento histórico profissional (Sysstat / SAR)**

---

# 1. Arquitetura do Sistema de Monitoramento

O monitoramento implementado possui três componentes principais:

```
/var/www/html/monitor-cpu/

index.html     → Dashboard Web
style.css      → Estilo do painel
api.php        → API de coleta de métricas
```

O dashboard consome dados da API via **AJAX a cada 3 segundos**.

---

# 2. Métricas Monitoradas

O painel coleta e apresenta:

### CPU

* Uso total de CPU
* Uso por core
* Histórico gráfico

### Temperatura

Temperatura do SoC do servidor.

Fonte:

```
/sys/class/thermal/thermal_zone0/temp
```

### Load Average

Indicador de carga do sistema:

```
sys_getloadavg()
```

### Memória RAM

* RAM utilizada
* RAM total
* Barra de uso em tempo real

Fonte:

```
free -m
```

### Uso de Disco

* Espaço utilizado
* Espaço total
* Barra visual de utilização

Fonte:

```
disk_total_space()
disk_free_space()
```

### IO de Disco

Taxa de leitura e escrita.

Fonte:

```
iostat -dx
```

### Containers Docker

Lista de containers com estado:

* running
* exited
* restarting

Fonte:

```
docker ps -a
```

### Container Frigate

Monitoramento específico do consumo de CPU do Frigate.

Fonte:

```
docker stats frigate
```

---

# 3. Interface Web do Dashboard

O dashboard apresenta:

* Gauge de CPU
* Gauge de temperatura
* Gráfico de load average
* Gráfico de CPU do Frigate
* Gráfico de IO de disco
* Gráfico de CPU por core
* Barra de RAM
* Barra de uso de disco
* Lista de containers Docker

Atualização automática a cada:

```
3 segundos
```

A interface utiliza:

* Chart.js
* GaugeJS
* JavaScript Fetch API

---

# 4. Instalação do Sysstat (Monitoramento Histórico Profissional)

## Instalar

```
apt update
apt install sysstat -y
```

## Ativar

```
systemctl enable sysstat
systemctl start sysstat
```

## Verificar status

```
systemctl status sysstat
```

Status esperado:

```
Active: active (exited)
```

Esse comportamento é normal pois o serviço é acionado por timers.

---

# 5. Confirmar Timers Ativos

```
systemctl list-timers | grep sysstat
```

Devem aparecer:

```
sysstat-collect.timer
sysstat-summary.timer
```

---

# 6. Verificar Arquivos de Log

```
ls -lh /var/log/sysstat/
```

Arquivos gerados:

```
sa01
sa02
sa03
...
```

Cada arquivo corresponde ao dia do mês.

---

# 7. Consultar Histórico

## Uso geral de CPU

```
sar -u
```

## Load average

```
sar -q
```

## CPU por core

```
sar -P ALL
```

## Analisar dia específico

```
sar -u -f /var/log/sysstat/sa22
```

---

# 8. Alterar Coleta para 1 Minuto

Editar:

```
nano /etc/cron.d/sysstat
```

Substituir:

```
5-55/10 * * * * root command -v debian-sa1 > /dev/null && debian-sa1 1 1
```

Por:

```
* * * * * root command -v debian-sa1 > /dev/null && debian-sa1 1 1
```

Reiniciar:

```
systemctl restart sysstat
```

---

# 9. Aumentar Retenção para 30 Dias

Editar:

```
nano /etc/sysstat/sysstat
```

Alterar:

```
HISTORY=7
```

Para:

```
HISTORY=30
```

Reiniciar:

```
systemctl restart sysstat
```

---

# 10. Monitoramento em Tempo Real (CLI)

## CPU ao vivo

```
sar -u 1 3
```

## Load atual

```
uptime
```

## Monitoramento detalhado

```
htop
```

---

# 11. Interpretação Técnica

### Servidor com 8 CPUs

| Load  | Situação  |
| ----- | --------- |
| 0 – 2 | Excelente |
| 2 – 4 | Normal    |
| 4 – 6 | Atenção   |
| 6 – 8 | Pesado    |
| > 8   | Saturado  |

---

# 12. Indicadores de Problema

Sinais de saturação do servidor:

* `%idle < 10%`
* `%iowait elevado`
* Load maior que número de CPUs
* travamento de SSH
* aumento repentino de IO de disco
* containers Docker reiniciando

---

# 13. Diagnóstico Pós-Travamento

Após reiniciar o servidor:

```
sar -u -f /var/log/sysstat/saXX
sar -q -f /var/log/sysstat/saXX
sar -P ALL -f /var/log/sysstat/saXX
```

Substituir **XX** pelo dia correspondente.

---

# 14. Ambiente Monitorado

Servidor configurado com:

* Ubuntu Linux
* Docker
* Frigate NVR
* 8 CPUs
* 16 GB RAM
* armazenamento local para gravações
* histórico de métricas com Sysstat
* dashboard web de monitoramento em tempo real

---

# 15. Dependências do Sistema

Para funcionamento completo do sistema de monitoramento, o servidor deve possuir os seguintes componentes instalados.

---

# Dependências do Servidor (Backend)

## PHP

Responsável por executar a API de coleta de métricas.

Instalação:

```bash
apt install php php-cli -y
```

Verificar:

```bash
php -v
```

---

## Servidor Web

Pode ser utilizado:

* Apache
* Nginx

Exemplo (Apache):

```bash
apt install apache2 -y
```

Diretório utilizado no projeto:

```
/var/www/html/monitor-cpu/
```

---

## Sysstat

Utilizado para monitoramento histórico do servidor.

Instalação:

```bash
apt install sysstat -y
```

Ferramentas fornecidas:

* sar
* iostat
* mpstat
* pidstat

---

## Docker

Necessário para monitorar containers e o Frigate.

Instalação:

```bash
apt install docker.io -y
```

Verificar:

```bash
docker ps
```

---

## Permissão Docker para o Apache

Para permitir que a API PHP execute comandos docker:

```bash
usermod -aG docker www-data
systemctl restart apache2
```

---

# Dependências do Frontend (Dashboard)

O dashboard utiliza bibliotecas JavaScript carregadas via CDN.

---

## Chart.js

Responsável pelos gráficos de:

* Load
* Disk IO
* CPU por core
* Frigate CPU

Fonte:

```
https://cdn.jsdelivr.net/npm/chart.js
```

---

## GaugeJS

Responsável pelos medidores circulares:

* CPU
* Temperatura

Fonte:

```
https://cdn.jsdelivr.net/npm/gaugeJS/dist/gauge.min.js
```

---

# Dependências de Comandos Linux Utilizados

A API utiliza comandos do sistema operacional para coleta de métricas.

| Comando              | Função                 |
| -------------------- | ---------------------- |
| `free`               | Uso de memória         |
| `iostat`             | IO de disco            |
| `docker stats`       | CPU de containers      |
| `docker ps`          | Status de containers   |
| `/proc/stat`         | CPU por core           |
| `/sys/class/thermal` | Temperatura do sistema |

---

# Dependências de Diretórios do Sistema

| Caminho                                 | Utilização              |
| --------------------------------------- | ----------------------- |
| `/proc/stat`                            | leitura de CPU          |
| `/sys/class/thermal/thermal_zone0/temp` | temperatura             |
| `/`                                     | cálculo de uso de disco |
| `/var/log/sysstat`                      | histórico de métricas   |

---

# Resumo das Dependências

Sistema mínimo necessário:

```
Ubuntu Server
Apache ou Nginx
PHP
Docker
Sysstat
Chart.js
GaugeJS
```

---

# Verificação Completa do Ambiente

Executar no servidor:

```bash
php -v
docker ps
sar -u
iostat
```

Se todos responderem corretamente, o sistema está pronto para uso.

---



