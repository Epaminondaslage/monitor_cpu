# 📦 Otimização e Isolamento do Container Frigate

Servidor: 10.0.0.139\
Sistema: Ubuntu Linux (Rockchip RK3288)\
CPU: 8 cores\
RAM: 16 GB

------------------------------------------------------------------------

# 🎯 Objetivo

Evitar travamentos do servidor causados por consumo excessivo de CPU e
memória pelo container do Frigate.

Problemas observados anteriormente:

-   Pico de uso de CPU
-   Travamento de SSH
-   Travamento do Apache
-   Sistema ficando sem resposta

------------------------------------------------------------------------

# 🔎 Diagnóstico Inicial

-   CPU: 8 cores físicos
-   RAM: 16GB (com folga disponível)
-   Swap inicial insuficiente
-   Frigate executando sem limites
-   Uso de CPU distribuído em todos os cores

Conclusão:

> O problema não era hardware insuficiente, e sim ausência de isolamento
> e limites no container.

------------------------------------------------------------------------

# 🧠 Estratégia Aplicada

## 1️⃣ Isolamento de CPU (Cpuset)

Definição aplicada:

-   CPUs 0--5 → Sistema operacional
-   CPUs 6--7 → Exclusivas para Frigate

Configuração aplicada no container:

    --cpuset-cpus="6-7"

------------------------------------------------------------------------

## 2️⃣ Limitação de CPU

    --cpus="2.0"

Limita o container a no máximo 200% de CPU (2 cores).\
Evita picos descontrolados.

------------------------------------------------------------------------

## 3️⃣ Limitação de Memória

    --memory="5g"
    --memory-swap="5g"

Define:

-   5GB de RAM máximo
-   Sem uso adicional de swap pelo container

Evita consumo excessivo de memória.

------------------------------------------------------------------------

## 4️⃣ Ajuste de OOM Score

    --oom-score-adj=500

Permite que o kernel finalize o container antes de comprometer o
sistema.

------------------------------------------------------------------------

## 5️⃣ Swap do Sistema

Criação de swap adicional:

    sudo fallocate -l 4G /swapfile2
    sudo chmod 600 /swapfile2
    sudo mkswap /swapfile2
    sudo swapon /swapfile2

Resultado final:

-   Swap total: 8GB

------------------------------------------------------------------------

## 6️⃣ Ajuste de Swappiness

Aplicado:

    sudo sysctl -w vm.swappiness=10

Persistido em:

    /etc/sysctl.d/99-swap.conf

Objetivo:

Reduzir uso agressivo de swap.

------------------------------------------------------------------------

# 🚀 Comando Final do Container

    docker run -d \
      --name frigate \
      --cpuset-cpus="6-7" \
      --cpus="2.0" \
      --memory="5g" \
      --memory-swap="5g" \
      --oom-score-adj=500 \
      ghcr.io/blakeblackshear/frigate:stable-rk

------------------------------------------------------------------------

# 📊 Monitoramento Implementado

Criado dashboard web personalizado com:

-   CPU total
-   CPU por core
-   Load average
-   RAM
-   Temperatura SoC
-   Uso específico do Frigate
-   IO de disco

Destaques:

-   Cores 6--7 monitorados como isolados
-   Alerta visual se Load \> 5

------------------------------------------------------------------------

# ✅ Resultado Obtido

-   Servidor permanece responsivo
-   SSH não trava
-   Apache permanece ativo
-   Frigate isolado em cores dedicados
-   Sistema protegido contra OOM

------------------------------------------------------------------------

# 🔮 Melhorias Futuras Possíveis

-   Detector automático de invasão de cores 0--5
-   Histórico real 24h via sysstat
-   WebSocket para atualização em tempo real
-   Alertas Telegram/MQTT
-   Integração com Prometheus

------------------------------------------------------------------------

# 📌 Conclusão Técnica

A instabilidade não estava relacionada à limitação de hardware, mas sim
à ausência de:

-   Controle de recursos
-   Isolamento de CPU
-   Limitação de memória
-   Política adequada de OOM

Com as alterações aplicadas, o sistema passou a operar de forma estável,
previsível e segura.
