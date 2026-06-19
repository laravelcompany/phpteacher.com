---
title: "The Risks of Free Conference Internet for PHP Developers"
description: "Learn why free conference WiFi is a security risk for PHP developers. How exposed Docker containers, MySQL databases, and npm scans can compromise your work."
pubDate: "2023-04-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP Security", "Conference WiFi", "Docker Security", "Network Security", "Nmap", "DevSecOps", "Cybersecurity", "Container Security"]
selected: false
---

Conference season is here. Spring brings rain, flowers, and tech conferences where developers gather to learn, network, and share ideas. It also brings a less welcome tradition: developers connecting their work machines to open conference wireless networks, unaware that everyone else on that network can see them.

Eric Mann's Security Corner column tackles this overlooked attack surface. The problem is not that conference WiFi exists — it is that developers treat it like their home network. They run Docker containers with exposed databases, work on production code with default credentials, and assume that the fellow attendee at the next table is too busy watching the keynote to scan for open ports.

They are wrong.

## What You'll Learn

- How tools like Nmap can enumerate every device on a conference network in seconds
- Why Docker containers expose services to the entire network by default
- How to configure Docker for local-only port binding
- The real-world story of a developer whose production database was cloned to a conference WiFi network
- Best practices for secure development at conferences and other public venues

## How Conference WiFi Exposes Your Machine

When you connect to a conference wireless network, you are joining a shared network segment with potentially hundreds of other attendees. The person streaming video in the back row, the sponsor representative checking email at the booth, and the security researcher in the front row testing tools — they all share the same Layer 2 network.

On a shared network, every device can see every other device's broadcast traffic. With the right tools, they can see much more. The conference WiFi password is posted on boards, announced from the stage, and shared on social media. There is no authentication beyond the password. Anyone in range can join.

The fundamental problem is trust. Your machine trusts the network, and the network trusts every connected device. An attacker needs only a laptop and a few open-source tools to map every host on the network, enumerate every open port, and begin probing for weak credentials.

## The Docker Exposure Problem

Docker is ubiquitous in PHP development. It provides consistent environments, isolates dependencies, and simplifies onboarding. But Docker's default network configuration exposes services broadly — sometimes to the entire network.

Consider this standard `docker-compose.yml` used by thousands of PHP developers:

```yaml
version: "3.5"

networks:
  front:
    name: front

volumes:
  db_data:

services:
  app:
    build:
      context: .
    restart: always
    ports:
      - "8080:8080"
    environment:
      SERVER_PORT: 8080
    networks:
      default:
      front:

  sql:
    image: mysql
    ports:
      - "3306:3306"
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: project
    volumes:
      - db_data:/var/lib/mysql
    networks:
      default:
      front:
```

This file looks innocuous. It defines a PHP application and a MySQL database, both on the same network. The application serves on port 8080. The database uses the standard MySQL port 3306.

The problem is on line 25: `"3306:3306"`. This tells Docker to bind port 3306 on the host machine to port 3306 in the container. By default, Docker binds to `0.0.0.0` — all network interfaces, including the one connected to conference WiFi.

Anyone on the same network can now attempt to connect to your local MySQL instance using the default `root` password you set for convenience during development.

## Scanning the Network With Nmap

Nmap (Network Mapper) is the industry-standard tool for network enumeration. With a single command, an attacker can discover every live host on the conference network:

```bash
nmap -sn 192.168.86.24/24
```

The `-sn` flag performs a ping scan. Nmap sends ICMP echo requests and TCP SYN packets to every IP in the range. Live hosts respond. Within seconds, the attacker has a complete list of active machines.

Once live hosts are identified, a more detailed scan reveals open ports:

```bash
nmap -p 1-10000 192.168.86.25
```

This scans the target for open ports in the 1-10000 range. Common targets include:

- **3306**: MySQL/MariaDB — exposed databases with default credentials
- **5432**: PostgreSQL
- **8080, 80**: HTTP services — PHP applications, admin panels
- **22**: SSH — a direct entry point if credentials are weak
- **2375, 2376**: Docker API — full container control

With just these two commands, an attacker at a conference can find dozens of vulnerable machines within minutes.

### The Real-World Attack

At a conference years ago, Eric Mann accepted a challenge from the organizers: hack an attendee live — with permission, of course. The process took less than five minutes:

1. **Scan the network** with Nmap to enumerate hosts
2. **Select a target** at random and scan for open ports
3. **Identify an exposed MySQL instance** on port 3306 with default credentials
4. **Connect** using `root` / `root`

The target was a WordPress developer working on a large e-commerce project. They had cloned their production database to a local Docker container to avoid syncing PCI data over the conference network. The irony was devastating: in trying to protect sensitive data from the network, they exposed it directly to every device on that network.

The developer's machine even revealed its server name, making identification trivial. The conference organizers contacted them directly. The database was taken down within minutes, but the lesson was clear: good intentions do not protect against bad configuration.

## Preventing Network Exposure

Two configuration changes prevent the kind of exposure described above. Both are trivial to implement and have no performance cost.

### 1. Use Internal Docker Networking

Docker Compose networks can be configured to stop proxying ports to the host network. Services within the Docker network can communicate using service names, and no ports are exposed externally:

```yaml
services:
  sql:
    image: mysql
    expose:
      - "3306"
    ports:
      - "127.0.0.1:3306:3306"
    # Or remove the ports section entirely for internal-only access
```

By removing the `ports` block or replacing it with `expose`, the service remains accessible to other containers on the same Docker network but is not reachable from the host machine's external interfaces.

### 2. Bind to Localhost Explicitly

When you do need to forward a port, always bind to `127.0.0.1` explicitly. This limits access to the local machine:

```yaml
services:
  app:
    ports:
      - "127.0.0.1:8080:8080"

  sql:
    ports:
      - "127.0.0.1:3306:3306"
```

The syntax `127.0.0.1:3306:3306` tells Docker to bind only to the loopback interface. The service is accessible from your machine but not from any external network — conference WiFi included.

### 3. Avoid Default Credentials

Even with local-only binding, never use default credentials in development. The MySQL `root` / `root` combination is the first credential pair any attacker tries. Use unique passwords, even for local development:

```yaml
services:
  sql:
    image: mysql
    environment:
      MYSQL_RANDOM_ROOT_PASSWORD: "yes"
      MYSQL_DATABASE: project
      MYSQL_USER: app_user
      MYSQL_PASSWORD: "a-unique-development-password"
```

`MYSQL_RANDOM_ROOT_PASSWORD` generates a strong password and logs it to the container output. This prevents accidental credential reuse while keeping automation intact.

## The Best Defense: Leave Your Laptop at Home

The most effective way to prevent a conference WiFi compromise is to not connect to conference WiFi in the first place. Eric Mann's advice is blunt but correct: if you are at a conference, be at the conference.

- **Leave your work laptop at the hotel**: Most conference sessions do not require a computer. Take notes on paper or a tablet.
- **Use your phone's hotspot**: Cellular data is not shared with other attendees. It is slower and may incur charges, but it is dramatically more secure than conference WiFi.
- **Keep your machine powered off**: If you must bring a laptop, keep it off during sessions. Only power it on in controlled environments.
- **Treat conference networks as hostile**: Any machine connected to conference WiFi should be treated as if it is running in a public space. No sensitive data, no production access, no default credentials.

## Real-World Use Cases

### Secure Conference Development Workflow

A PHP developer attending a conference can set up a travel-safe development environment by creating a dedicated Docker Compose file that binds all ports to localhost only, uses random passwords, and includes no production data.

### Team Conferences and Hackathons

Teams attending hackathons should agree on a security baseline before the event. Mandate localhost-only binding, enforce strong passwords, and scan each other's machines as a friendly exercise in security awareness.

### Remote Work in Public Spaces

The same risks apply to coffee shops, co-working spaces, hotel lobbies, and airport lounges. Any public WiFi network is an untrusted environment. Apply the same precautions.

## Best Practices

- **Bind all Docker ports to 127.0.0.1**: Default binding to `0.0.0.0` exposes services to the entire network.
- **Use strong, unique passwords for local services**: Never use `root` / `root` for any database.
- **Use a VPN**: A VPN encrypts all traffic between your machine and the VPN endpoint, protecting against sniffing and man-in-the-middle attacks on untrusted networks.
- **Disable file sharing**: Turn off AirDrop, Windows file sharing, and Samba when on untrusted networks.
- **Patch and update**: Keep your operating system, Docker, and all software up to date. Patches often fix remotely exploitable vulnerabilities.

## Common Mistakes to Avoid

- **Using default Docker Compose configurations**: The examples in most tutorials bind to `0.0.0.0`. Always modify them for your context.
- **Cloning production data to a local machine on an untrusted network**: If you need production data offline, do it before you travel, or use a VPN.
- **Assuming conference networks are secure because they require a password**: A shared password is not authentication. Anyone can obtain it.
- **Leaving SSH enabled on conference networks**: If you do not need remote access, turn off SSH. If you must have it, use key-based authentication only.
- **Ignoring your own machine's firewall**: Enable the operating system firewall and configure it to block inbound connections from non-local networks.

## Frequently Asked Questions

**Can someone really scan my machine in seconds?**
Yes. Nmap can scan an entire /24 subnet (254 IPs) for open ports in under 30 seconds. A targeted scan of a single host takes less than a second.

**Does Docker's default configuration really expose my database?**
Yes. The `ports` directive in Docker Compose binds to `0.0.0.0` by default, making the service accessible from any network interface on the host machine, including WiFi.

**Is it safe to use a VPN on conference WiFi?**
A VPN encrypts your traffic and protects against sniffing, but it does not prevent port scans. If your VPN connection drops, your machine is exposed. Use localhost binding as your primary defense.

**What about HTTPS? Does it protect my web application?**
HTTPS protects data in transit between the browser and the server. It does not protect against direct connections to exposed ports like 3306 or 22.

**Should I be worried about my phone?**
Yes. Your phone contains sensitive data and connects to the same conference network. Disable file sharing, use a VPN, and avoid accessing sensitive accounts over conference WiFi.

**How do I check if my Docker services are exposed?**
Run `docker ps` to see port mappings. Look for `0.0.0.0:3306->3306/tcp`. If you see `0.0.0.0`, your service is bound to all interfaces.

**Can I use Docker Desktop's built-in security features?**
Docker Desktop includes features like the ability to bind to specific IPs. Use them. Also consider tools like `docker-scan` to check for vulnerabilities in your images.

**What should I do if I discover an exposed service at a conference?**
Contact the person whose machine is exposed, or notify conference staff. If you cannot identify the owner, the safest approach is to disconnect from the network.

## Conclusion

Free conference internet is a convenience, not a right — and certainly not a security guarantee. Eric Mann's column serves as a wake-up call for PHP developers who treat public WiFi as an extension of their trusted office network.

The fixes are simple. Bind Docker ports to `127.0.0.1`. Use strong passwords. Scan your own machine before connecting to a conference network. Better yet, leave the work laptop at the hotel and use your phone's hotspot for essential connectivity.

Conference season should be about learning, connecting, and sharing ideas — not about explaining to your CTO why the production database credentials were compromised on a public network. A few seconds of configuration prevent hours of damage control.

Stay curious. Stay secure. And next time you are at a conference, take a moment to think about what your machine is announcing to everyone in the room.
