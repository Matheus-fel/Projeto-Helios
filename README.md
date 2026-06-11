# WebServer - Docker (Nginx + PHP-FPM + MySQL)

## Download para a Inteligencia Artificial presente no projeto
---->[Helios AI](huggingface.co/Edmurk/Helios) <----
## Arquitetura

- **Nginx** → Servidor web (porta 8050)
- **PHP-FPM** → Processamento PHP (8.2)
- **MySQL 8.0** → Banco de dados
- **phpMyAdmin** → Interface web (porta 8051)

## Redes Docker (Isolamento)

- **`frontend_net`** → Rede pública (exposta externamente)
  - Contém: Nginx
- **`backend_net`** → Rede interna (isolada - `internal: true`)
  - Contém: PHP-FPM, MySQL, phpMyAdmin

**Benefício**: O MySQL e o PHP só são acessíveis através do Nginx. Não exposto diretamente para fora.

## Volumes Persistentes (Simulação NFS)

- `mysql_data` → Persistência do banco de dados (equivalente a um volume montado via NFS)
- `./initdb` → Scripts SQL de inicialização (executados apenas na primeira vez)

## Como Executar

```bash
# 1. Entre na pasta Server
cd Server

# 2. Subir os containers
docker compose up --build -d

# 3. Ver logs
docker compose logs -f nginx

# 4. Ver redes
docker network ls
docker network inspect backend_net


## Redes Docker - Isolamento

| Rede           | Tipo       | Internal | Containers                     | Finalidade                     |
|----------------|------------|----------|--------------------------------|--------------------------------|
| frontend_net   | bridge     | false    | nginx                          | Exposição externa (porta 8050) |
| backend_net    | bridge     | true     | php, mysql, phpmyadmin         | Isolamento interno de banco    |

**Benefício do isolamento**: O banco de dados MySQL fica inacessível diretamente da máquina host ou da internet, só sendo acessível via Nginx → PHP.
