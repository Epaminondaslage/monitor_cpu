# Monitoramentor de CPU -- Servidor Ubuntu com Docker (Frigate)
## Servidor 10.0.0.139
## Objetivo

Implementar monitoramento profissional de carga de CPU, memória e load
average para:

-   Detectar sobrecarga
-   Registrar histórico mesmo após travamentos
-   Permitir análise técnica pós-falha
-   Monitorar ambiente com Docker + Frigate

------------------------------------------------------------------------

# 1. Instalação do Sysstat (Monitoramento Histórico Profissional)

## Instalar

``` bash
apt update
apt install sysstat -y
```

## Ativar

``` bash
systemctl enable sysstat
systemctl start sysstat
```

## Verificar status

``` bash
systemctl status sysstat
```

Status esperado:

    Active: active (exited)

Isso é normal.

------------------------------------------------------------------------

# 2. Confirmar Timers Ativos

``` bash
systemctl list-timers | grep sysstat
```

Deve aparecer:

-   sysstat-collect.timer
-   sysstat-summary.timer

------------------------------------------------------------------------

# 3. Verificar Arquivos de Log

``` bash
ls -lh /var/log/sysstat/
```

Arquivos como:

    sa21

(21 = dia do mês)

------------------------------------------------------------------------

# 4. Consultar Histórico

## Uso geral de CPU

``` bash
sar -u
```

## Load average

``` bash
sar -q
```

## CPU por core

``` bash
sar -P ALL
```

## Analisar dia específico

``` bash
sar -u -f /var/log/sysstat/sa22
```

------------------------------------------------------------------------

# 5. Alterar Coleta para 1 Minuto

Editar:

``` bash
nano /etc/cron.d/sysstat
```

Substituir:

    5-55/10 * * * * root command -v debian-sa1 > /dev/null && debian-sa1 1 1

Por:

    * * * * * root command -v debian-sa1 > /dev/null && debian-sa1 1 1

Reiniciar:

``` bash
systemctl restart sysstat
```

------------------------------------------------------------------------

# 6. Aumentar Retenção para 30 Dias

Editar:

``` bash
nano /etc/sysstat/sysstat
```

Alterar:

    HISTORY=7

Para:

    HISTORY=30

Reiniciar:

``` bash
systemctl restart sysstat
```

------------------------------------------------------------------------

# 7. Monitoramento em Tempo Real

## CPU ao vivo

``` bash
sar -u 1 3
```

## Load atual

``` bash
uptime
```

## Monitoramento detalhado

``` bash
htop
```

------------------------------------------------------------------------

# 8. Interpretação Técnica

## Load average (Servidor com 8 CPUs)

  Load   Situação
  ------ -----------
  0--2   Excelente
  2--4   Normal
  4--6   Atenção
  6--8   Pesado
  \>8    Saturado

## Indicadores de Problema

-   %idle \< 10%
-   %iowait alto
-   Load \> número de CPUs
-   SSH lento ou travando

------------------------------------------------------------------------

# 9. Diagnóstico Pós-Travamento

Após reiniciar o servidor:

``` bash
sar -u -f /var/log/sysstat/saXX
sar -q -f /var/log/sysstat/saXX
sar -P ALL -f /var/log/sysstat/saXX
```

Substituir XX pelo dia correspondente.

------------------------------------------------------------------------

# Ambiente Monitorado

-   Ubuntu Linux
-   Docker
-   Frigate isolado por CPU
-   8 CPUs
-   16 GB RAM
-   Swap configurado
-   Monitoramento minuto a minuto
-   Histórico de 30 dias

------------------------------------------------------------------------

Documento técnico gerado para referência operacional.
