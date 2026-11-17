# Acceso remoto al servidor (SSH + Tailscale)

El servidor es una VM en el Mac de Cami. Para que Santiago entre desde su casa sin abrir puertos en el router se usa **Tailscale** (VPN privada, gratis, funciona detrás de NAT y en el Wi-Fi de la U).

| Medida | Para qué |
|---|---|
| Tailscale | Acceso remoto sin exponer el servidor a internet |
| Un usuario por persona | Saber quién hizo qué (`last`, `journalctl`) |
| Solo llaves SSH | Sin contraseñas por SSH |
| Root bloqueado | Nadie entra directo como root |
| fail2ban | Bloquea IPs que fallan muchas veces |

---

## 1. Tailscale en el servidor (Cami)

```bash
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up --hostname sanluquitas
```

Abrir el link que sale y entrar con la cuenta de Cami. Luego:

```bash
tailscale ip -4
sudo ufw allow in on tailscale0
```

Anotar la IP `100.x.y.z`.

## 2. Compartir el servidor con Santiago (Cami)

https://login.tailscale.com/admin/machines → `sanluquitas` → `...` → **Share** → copiar el link y mandárselo a Santiago.

## 3. Llave SSH de Santiago (Santiago, en su PC)

Mac / Linux / Windows (PowerShell):

```bash
ssh-keygen -t ed25519 -C "santiago-sanluquitas"
```

Mostrar la llave **pública** y mandársela a Cami:

```bash
# Mac / Linux
cat ~/.ssh/id_ed25519.pub
# Windows PowerShell
type $env:USERPROFILE\.ssh\id_ed25519.pub
```

Instalar Tailscale (https://tailscale.com/download), entrar y aceptar el link del paso 2.

## 4. Usuario de Santiago (Cami, en el servidor)

```bash
sudo adduser --gecos "Santiago Steven Ruiz" santiago
sudo usermod -aG sudo,docker santiago

sudo mkdir -p /home/santiago/.ssh
echo "PEGAR_AQUI_LA_LLAVE_PUBLICA_DE_SANTIAGO" | sudo tee /home/santiago/.ssh/authorized_keys
sudo chown -R santiago:santiago /home/santiago/.ssh
sudo chmod 700 /home/santiago/.ssh
sudo chmod 600 /home/santiago/.ssh/authorized_keys
```

La contraseña de `adduser` solo sirve para `sudo`, no para entrar por SSH.

## 5. Carpeta compartida del proyecto (Cami)

```bash
sudo groupadd devs
sudo usermod -aG devs admsl
sudo usermod -aG devs santiago
sudo mkdir -p /opt/san-luquitas-infra
sudo chown admsl:devs /opt/san-luquitas-infra
sudo chmod 2775 /opt/san-luquitas-infra
```

## 6. Endurecer SSH (Cami)

Antes de esto, verificar que Cami ya entra con llave (`ssh-copy-id`). **Dejar una sesión SSH abierta** mientras se prueba.

```bash
sudo tee /etc/ssh/sshd_config.d/01-sanluquitas.conf > /dev/null <<'EOF'
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
AllowUsers admsl santiago
MaxAuthTries 3
LoginGraceTime 30
EOF

sudo sshd -t && sudo systemctl restart ssh
sudo apt -y install fail2ban
sudo systemctl enable --now fail2ban
```

El archivo se llama `01-` porque sshd usa el primer valor que lee, y Ubuntu trae `50-cloud-init.conf` que puede activar contraseñas.

## 7. Conectarse (Santiago)

```bash
ssh santiago@100.x.y.z
```

Atajo opcional en `~/.ssh/config`:

```
Host sanluquitas
    HostName 100.x.y.z
    User santiago
    IdentityFile ~/.ssh/id_ed25519
```

Luego solo: `ssh sanluquitas`. También sirve con VS Code → Remote-SSH.

Para abrir Moodle y el portal desde su navegador, agregar al archivo hosts:

- Mac/Linux: `sudo nano /etc/hosts`
- Windows: Bloc de notas como administrador → `C:\Windows\System32\drivers\etc\hosts`

```
100.x.y.z   sanluquitas.local
```

## 8. Verificación

| Prueba | Comando | Resultado esperado |
|---|---|---|
| Tailscale activo | `tailscale status` | Aparece `sanluquitas` |
| Santiago entra | `ssh santiago@100.x.y.z` | Entra sin pedir contraseña |
| Contraseña bloqueada | `ssh -o PubkeyAuthentication=no santiago@100.x.y.z` | `Permission denied (publickey)` |
| Root bloqueado | `ssh root@100.x.y.z` | `Permission denied` |
| fail2ban | `sudo fail2ban-client status sshd` | Jail activa |
| Quién entró | `last -n 10` y `journalctl -u ssh --since today` | Registro de accesos |

## Importante

- La VM solo está disponible si el Mac de Cami está prendido y sin dormir.
- Iniciar la VM sin ventana: `VBoxManage startvm "sanluquitas-server" --type headless`
- Evitar que el Mac duerma mientras Santiago trabaja: `caffeinate -dimsu` en una Terminal.
