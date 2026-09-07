# AGOS: Dual-Platform Disaster Monitoring System

> **Undergraduate Thesis** | Ateneo de Davao University  
> **Core Architecture:** Multi-Task NLP (XLM-RoBERTa) & K-Means++ Clustering for Barangay-Level Flood Validation  
> **Repository:** [leyquinn/AGOS_Website](https://github.com/leyquinn/AGOS_Website)

---

## 📌 Project Overview

**AGOS** is a real-time crowdsourced flood monitoring and disaster response ecosystem built for local government units and emergency responders. By integrating social media data harvesting and citizen report submissions, AGOS uses advanced Machine Learning techniques to eliminate false alarms, cluster geographic incident hotspots, and provide actionable flood intelligence.

* **Multi-Task XLM-RoBERTa:** Validates noisy, code-switched (English/Cebuano/Tagalog) social media and citizen reports for relevance, severity, and location accuracy.
* **K-Means++ Spatial Clustering:** Group validated reports dynamically by barangay and GPS coordinates to identify high-risk flood zones.
* **Dual-Platform Integration:** Decoupled architecture supporting a responsive Web Administrative Portal and a lightweight Mobile App API.

---

## 🛠️ Tech Stack

* **Backend Framework:** Laravel 12 (PHP 8.2+)
* **Frontend Framework:** Tailwind CSS, Blade Components, Vite
* **Database:** MySQL
* **NLP & Clustering Engine:** Python / XLM-RoBERTa Transformer Models & K-Means++
* **API Engine:** RESTful JSON Services (`app/Http/Controllers/Api`)

---

## 📂 Project Structure

```text
agos/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # Mobile App API Controllers (Auth, Reports, Maps)
│   │   │   └── Web/          # Admin Web Portal Controllers (Dashboard, Harvesting, Analytics)
│   │   └── Resources/        # JSON Data Transformers for Mobile Clients
│   └── Models/               # Shared Database Models (User, Report, Incident, etc.)
├── resources/
│   └── views/
│       ├── components/       # UI Partials (Navbar, Sidebar)
│       ├── layouts/          # Master Wrappers (app.blade.php, guest.blade.php)
│       ├── auth/             # Login & Access Views
│       ├── operations/       # Dispatch & Field Unit Views
│       ├── reports/          # Verification & Incident Logs
│       ├── harvesting/       # Social Media Crawlers & NLP Feeds
│       ├── analytics/        # K-Means++ Hotspot Maps & Charts
│       └── landing.blade.php # Public Overview Page
└── routes/
    ├── api.php               # Mobile API Endpoints
    └── web.php               # Web Portal Routes