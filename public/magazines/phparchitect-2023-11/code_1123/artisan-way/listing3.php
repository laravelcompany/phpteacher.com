└── app
    ├── Deliveries
    │     ├── Commands
    │     ├── Controllers
    │     │     └── AcceptNewDeliveryController.php
    │     ├── DeliveryServiceProvider
    │     ├── Events
    │     │     ├── DeliveryAccepted.php
    │     │     ├── DeliveryProcessed.php
    │     │     └── DeliveryReceived.php
    │     ├── Jobs
    │     │     └── DeliveryProcessed.php
    │     ├── Routes
    │     │     ├── api.php
    │     │     └── web.php
    │     ├── Services
    │     │     └── IncomingDeliveryService.php
    │     └── Validators
    │         └── NewDeliveryValidator.php
    ├── Finance
    ├── Logistics
    └── StockManagement