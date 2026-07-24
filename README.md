```markdown
# 🧠 AI Recruitment Risk Analyzer - Social Media Screening for HR

**Proyek** : Sistem screening jejak digital kandidat berbasis AI multi-model  
**Target** : Selesai Minggu, 26 Juli 2026  
**Tim** : 3 Orang (Person 1, Person 2, Person 3)  
**Teknologi** : Laravel 12, FastAPI, React, PostgreSQL, Redis, Hugging Face, PaddleOCR, Whisper

---

## 📋 Ringkasan Proyek

Sistem ini menganalisis postingan publik kandidat dari berbagai platform (Facebook, Instagram, X/Twitter, LinkedIn, TikTok, Threads) menggunakan arsitektur AI berlapis.

### Apa yang Dianalisis?

| Jenis Konten     | Sumber                      | Metode Ekstraksi       |
| ---------------- | --------------------------- | ---------------------- |
| Teks postingan   | Postingan sendiri           | Langsung dari API      |
| Teks komentar    | Komentar di postingan orang | Langsung dari API      |
| Teks balasan     | Reply ke komentar orang     | Langsung dari API      |
| Teks dari gambar | Meme, screenshot, GIF       | OCR (PaddleOCR)        |
| Transkrip video  | Video pendek, TikTok, Reels | Whisper Speech-to-Text |

### Arsitektur Sistem
```

                  Candidate Username
                         │
                         ▼
              Crawler (Public Data Only)
                         │
      ┌──────────────────┼──────────────────┐
      │                  │                  │
      ▼                  ▼                  ▼

Facebook X/Twitter LinkedIn
Instagram Threads TikTok
│
▼
OCR (gambar) + Speech-to-Text (video)
│
▼
Normalisasi Text
│
▼
┌─────────────────────────────────────────────┐
│ AI Analysis Engine │
├─────────────────────────────────────────────┤
│ Detoxify │
│ HateBERT │
│ XLM-RoBERTa (Multilingual) │
│ Qwen 3 / GPT (Context Analysis) │
└─────────────────────────────────────────────┘
│
▼
Risk Scoring Engine
│
▼
HR Dashboard

```

### Model AI yang Digunakan

| Layer | Model | Fungsi |
|-------|-------|--------|
| 1 | **Detoxify** | Deteksi cepat: toxicity, threat, obscene, identity attack, insult, sexual explicit |
| 2 | **HateBERT** | Spesialis hate speech, offensive, abusive (khusus data toxic) |
| 3 | **XLM-RoBERTa** | Multilingual (Indonesia + Inggris + campuran + bahasa gaul) |
| 4 | **Qwen 3 / GPT** | Analisis konteks (membedakan bercanda vs serangan, kutipan vs opini) |

### Multimodal Processing

- **Gambar (Meme, Screenshot, GIF)** → PaddleOCR / EasyOCR → Teks
- **Video** → Whisper → Transkrip
- **Gambar (simbol)** → CLIP / SigLIP → Deteksi simbol terlarang (Nazi, ISIS, kekerasan)

### Risk Scoring

| Kategori | Bobot |
|----------|-------|
| Hate Speech | 35% |
| Threat | 30% |
| Harassment | 15% |
| Violence | 10% |
| Explicit | 5% |
| Illegal Activity | 5% |

**Contoh Output:**
```

Hate Speech: 92%
Threat: 12%
Harassment: 67%
↓
Risk Score: 81 / 100 → HIGH

```

---

## 🧩 Pembagian Tugas (3 Orang)

### 👤 Person 1 – Backend & Data Pipeline (LARAVEL)

**Bertanggung jawab atas:**
Pengumpulan data, penyimpanan, antrian pemrosesan, dan API untuk frontend.

#### Teknologi & Komponen

| Komponen | Teknologi | Deskripsi |
|----------|-----------|-----------|
| Database | PostgreSQL | Model: `Candidate`, `SocialPost`, `AnalysisResult` |
| Backend API | Laravel 12 | RESTful endpoints untuk dashboard |
| Crawler | HTTP Client (Guzzle) | Scraping publik dari 6 platform |
| Queue | Redis | Dispatch job setiap kali post masuk |
| OCR/STT Integration | FastAPI (HTTP) | Kirim gambar/video ke AI Service, simpan hasil |
| Endpoints | Laravel | `/api/candidates`, `/api/candidates/{id}/risk`, `/api/candidates/{id}/posts` |

#### Struktur Folder (Backend Laravel)

```

app/
├── Console/
│ └── Commands/
│ └── CrawlCandidates.php
├── Http/
│ └── Controllers/
│ ├── Api/
│ │ ├── CandidateController.php
│ │ └── AnalysisController.php
├── Jobs/
│ └── AnalyzePostJob.php
├── Models/
│ ├── Candidate.php
│ ├── SocialPost.php
│ ├── AnalysisResult.php
│ └── CrawlerLog.php
└── Services/
├── CrawlerService.php
├── PlatformManager.php
├── AIService.php
└── Crawlers/
├── BaseCrawler.php
├── InstagramCrawler.php
├── TwitterCrawler.php
├── FacebookCrawler.php
└── TikTokCrawler.php

```

#### Sub-tugas Harian (Person 1)

| Hari | Tugas |
|------|-------|
| **Kamis** | Setup Laravel + PostgreSQL + Redis, buat migration & model |
| **Jumat** | Implementasi crawler 4 platform, integrasi dengan FastAPI |
| **Sabtu** | Buat queue job + worker, API endpoints |
| **Minggu** | Finalisasi, testing, integrasi dengan frontend |

---

### 👤 Person 2 – AI Service & Multimodal Analysis (FASTAPI)

**Bertanggung jawab atas:**
Semua proses analisis konten (teks, gambar, video) dan scoring.

#### Teknologi & Komponen

| Komponen | Teknologi | Deskripsi |
|----------|-----------|-----------|
| API Service | FastAPI | Endpoint: `/analyze-text`, `/analyze-image`, `/analyze-video`, `/analyze-post` |
| Text Models | Detoxify, HateBERT, XLM-RoBERTa | Load dari Hugging Face, kembalikan skor |
| OCR | PaddleOCR | Ekstrak teks dari gambar (meme, screenshot, GIF) |
| Speech-to-Text | Whisper | Transkrip video |
| Image Safety | CLIP / SigLIP | Deteksi simbol terlarang |
| LLM Context | Qwen 3 14B / OpenAI API | Analisis konteks, kategorisasi, penjelasan |
| Scoring Engine | Python | Hitung risk score berdasarkan bobot |

#### Struktur Folder (AI Service)

```

ai-service/
├── app/
│ ├── main.py
│ ├── models/
│ │ ├── detoxify_model.py
│ │ ├── hatebert_model.py
│ │ └── xlmr_model.py
│ ├── ocr/
│ │ └── paddleocr_processor.py
│ ├── stt/
│ │ └── whisper_processor.py
│ ├── llm/
│ │ └── qwen_analyzer.py
│ ├── vision/
│ │ └── clip_detector.py
│ └── scoring/
│ └── risk_scorer.py
└── requirements.txt

```

#### Sub-tugas Harian (Person 2)

| Hari | Tugas |
|------|-------|
| **Kamis** | Setup FastAPI, load Detoxify + HateBERT + XLM-RoBERTa |
| **Jumat** | Integrasi PaddleOCR + Whisper, buat endpoint image & video |
| **Sabtu** | Integrasi Qwen 3 / GPT untuk context analysis |
| **Minggu** | Implementasi scoring engine, test dengan dataset dummy |

---

### 👤 Person 3 – Frontend Dashboard (REACT)

**Bertanggung jawab atas:**
Tampilan HR-friendly untuk melihat hasil screening.

#### Teknologi & Komponen

| Komponen | Teknologi | Deskripsi |
|----------|-----------|-----------|
| Framework | React + Vite + TailwindCSS | Struktur dashboard |
| Tabel | TanStack Table | Daftar kandidat dengan filter & sorting |
| Charts | Recharts / Chart.js | Radar chart / bar chart untuk ringkasan risiko |
| HTTP Client | Axios / React Query | Panggil API Laravel |
| Modal | Shadcn/ui / Radix | Popup detail posting berisiko dengan alasan LLM |

#### Struktur Folder (Frontend React)

```

frontend-react/
├── src/
│ ├── components/
│ │ ├── Layout/
│ │ ├── Candidates/
│ │ ├── Detail/
│ │ └── Modal/
│ ├── pages/
│ │ ├── Dashboard.jsx
│ │ └── CandidateDetail.jsx
│ ├── services/
│ │ └── api.js
│ └── hooks/
│ └── useCandidates.js
└── package.json

```

#### Sub-tugas Harian (Person 3)

| Hari | Tugas |
|------|-------|
| **Kamis** | Setup React + Vite + Tailwind, layout dasar |
| **Jumat** | Halaman daftar kandidat dengan TanStack Table |
| **Sabtu** | Halaman detail kandidat (profil, chart, ringkasan) |
| **Minggu** | Modal untuk posting berisiko, finalisasi UI |

---

## 🔗 Integrasi Antar Bagian

```

┌─────────────────────────────────────────────────────────────┐
│ Person 3 (React) │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Dashboard HR (Daftar Kandidat + Detail + Modal) │ │
│ └──────────────────────┬──────────────────────────────┘ │
│ │ HTTP │
└─────────────────────────┼─────────────────────────────────┘
▼
┌─────────────────────────────────────────────────────────────┐
│ Person 1 (Laravel API + Queue) │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Crawler │→ │ PostgreSQL │ │ Redis Queue │ │
│ └──────────────┘ └──────────────┘ └──────┬───────┘ │
│ │ │
└───────────────────────────────────────────────┼────────────┘
│ HTTP
▼
┌─────────────────────────────────────────────────────────────┐
│ Person 2 (FastAPI AI Service) │
│ ┌──────────┐ ┌──────────┐ ┌─────────┐ ┌─────────────┐ │
│ │Detoxify │ │HateBERT │ │XLM-RoBERTa│ │ Qwen 3/GPT │ │
│ └──────────┘ └──────────┘ └─────────┘ └──────┬──────┘ │
│ ┌──────────┐ ┌──────────┐ ┌────────────────┐ │
│ │PaddleOCR │ │ Whisper │ │ CLIP/SigLIP │ │
│ └──────────┘ └──────────┘ └────────────────┘ │
│ │ │
│ ▼ │
│ ┌──────────────────────────────────────────────────┐ │
│ │ Risk Scoring Engine │ │
│ └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘

````

---

## 🚀 Panduan Setup Lengkap

### 1. Backend Laravel (Person 1)

```bash
# Clone repository
git clone https://github.com/yourusername/ai-recruitment-risk-analyzer.git
cd ai-recruitment-risk-analyzer/backend-laravel

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database di .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=recruitment_risk
DB_USERNAME=postgres
DB_PASSWORD=yourpassword

# Buat database di PostgreSQL
psql -U postgres -c "CREATE DATABASE recruitment_risk;"

# Jalankan migration
php artisan migrate

# Jalankan seeder (data dummy)
php artisan db:seed

# Jalankan queue worker (terminal terpisah)
php artisan queue:work

# Jalankan server
php artisan serve
````

### 2. AI Service FastAPI (Person 2)

```bash
cd ai-service

# Buat virtual environment
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Jalankan server
uvicorn app.main:app --reload --port 8000
```

### 3. Frontend React (Person 3)

```bash
cd frontend-react

# Install dependencies
npm install

# Jalankan development server
npm run dev
```

---

## 📊 API Endpoints

### Candidate Endpoints

| Method | Endpoint                     | Deskripsi                |
| ------ | ---------------------------- | ------------------------ |
| GET    | `/api/candidates`            | Daftar semua kandidat    |
| POST   | `/api/candidates`            | Tambah kandidat baru     |
| GET    | `/api/candidates/{id}`       | Detail kandidat          |
| POST   | `/api/candidates/{id}/crawl` | Crawl postingan kandidat |
| POST   | `/api/candidates/crawl-all`  | Crawl semua kandidat     |

### Analysis Endpoints

| Method | Endpoint                                | Deskripsi                 |
| ------ | --------------------------------------- | ------------------------- |
| GET    | `/api/analysis/summary/{candidateId}`   | Ringkasan risiko          |
| GET    | `/api/analysis/high-risk/{candidateId}` | Postingan berisiko tinggi |
| GET    | `/api/analysis/trends`                  | Tren risiko harian        |

### Contoh Response

```json
{
  "profile": {
    "id": 1,
    "username": "johndoe",
    "full_name": "John Doe",
    "platform": "twitter"
  },
  "risk_summary": {
    "total": 100,
    "safe": 92,
    "need_review": 7,
    "high_risk": 1,
    "risk_level": "MEDIUM",
    "risk_score": 55.5
  },
  "posts": [
    {
      "id": 1,
      "text": "Halo semua!",
      "posted_at": "2026-07-24 10:00:00",
      "analysis": {
        "risk_score": 5,
        "risk_level": "LOW",
        "context_explanation": "Postingan normal, tidak ada indikasi risiko"
      }
    }
  ]
}
```

---

## 🛠️ Environment Variables

### Laravel (.env)

```env
APP_NAME="AI Recruitment Risk Analyzer"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=recruitment_risk
DB_USERNAME=postgres
DB_PASSWORD=yourpassword

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# AI Service
AI_SERVICE_URL=http://localhost:8000

# API Keys (isi sesuai punya Anda)
INSTAGRAM_ACCESS_TOKEN=your_token
TWITTER_BEARER_TOKEN=your_token
FACEBOOK_ACCESS_TOKEN=your_token
RAPIDAPI_KEY=your_token
```

### FastAPI (.env)

```env
OPENAI_API_KEY=sk-xxx  # jika pakai OpenAI
QWEN_MODEL_PATH=/path/to/qwen-14b  # jika lokal
```

---

## 🔧 Perintah Berguna

### Backend Laravel

```bash
# Migrasi database
php artisan migrate:fresh --seed

# Jalankan crawler
php artisan crawl:candidates --platform=twitter --limit=50

# Jalankan crawler semua platform
php artisan crawl:candidates --all --limit=20

# Jalankan queue worker
php artisan queue:work --tries=3 --timeout=300

# Cek queue failed
php artisan queue:failed

# Clear cache
php artisan optimize:clear
```

### AI Service

```bash
# Jalankan FastAPI
uvicorn app.main:app --reload --port 8000

# Test endpoint
curl http://localhost:8000/health
```

### Frontend

```bash
# Jalankan development
npm run dev

# Build untuk production
npm run build
```

---

## ✅ Kriteria Kelulusan (Done Checklist)

### Person 1 (Backend)

- [ ] Laravel + PostgreSQL + Redis berjalan
- [ ] Migration & seeder selesai
- [ ] Crawler 4 platform berfungsi (Instagram, Twitter, Facebook, TikTok)
- [ ] Queue job berjalan (Redis worker)
- [ ] API endpoints siap
- [ ] Integrasi dengan FastAPI berhasil

### Person 2 (AI Service)

- [ ] FastAPI berjalan di port 8000
- [ ] Detoxify, HateBERT, XLM-RoBERTa terload
- [ ] PaddleOCR dan Whisper berfungsi
- [ ] Qwen 3 / GPT untuk context analysis
- [ ] Scoring engine menghasilkan risk score
- [ ] API documentation (Swagger/OpenAPI)

### Person 3 (Frontend)

- [ ] React + Vite + Tailwind berjalan
- [ ] Halaman daftar kandidat dengan TanStack Table
- [ ] Filter & sorting berfungsi
- [ ] Halaman detail kandidat (profil, chart, ringkasan)
- [ ] Modal untuk posting berisiko
- [ ] Integrasi API Laravel berhasil

### Integrasi (Semua)

- [ ] React → Laravel → FastAPI → balik ke React
- [ ] Data mengalir dengan benar
- [ ] Demo lokal berjalan di masing-masing laptop

---

## 📞 Koordinasi Tim

| Aspek              | Detail                                                |
| ------------------ | ----------------------------------------------------- |
| **Daily Check-in** | Setiap pagi jam 09.00 (15 menit) via Discord/WhatsApp |
| **Repo**           | Satu repository dengan 3 folder                       |
| **API Contract**   | Tentukan format JSON antar service di hari pertama    |
| **Issue Tracking** | Gunakan Trello / GitHub Projects                      |

### API Contract (Wajib Disepakati)

**Laravel → FastAPI**

```json
POST /api/analyze-persona
{

    "name": "string (required)",
    "social_media": [
      {
        "platform": "string (required)",
        "username": "string (required)",
        "email": "string (nullable)",
        "post_count": "integer (optional, default: 0)",
        "profile_url": "string (optional, nullable)",
        "avatar_url": "string (optional, nullable)",
        "created_at": "string (required, ISO 8601 date-time)",
        "updated_at": "string (required, ISO 8601 date-time)",
        "posts": [
          {
            "text": "string (optional, nullable)",
            "image_url": "string (optional, nullable)",
            "video_url": "string (optional, nullable)",
          }
        ]
      }
    ]

}
```

**FastAPI → Laravel**

```json
{
  "name": "string (required)",
  "overall_risk_score": "integer (0-100, required)",
  "overall_risk_level": "string (required, enum: LOW | MEDIUM | HIGH | CRITICAL)",
  "aggregated_scores": {
    "toxicity": "float (0-1, required)",
    "threat": "float (0-1, required)",
    "insult": "float (0-1, required)",
    "hate_speech": "float (0-1, required)"
  },
  "summary": {
    "total_posts_analyzed": "integer (required)",
    "high_risk_posts_count": "integer (required)"
  },
  "social_media": [
    {
      "platform": "string (required)",
      "username": "string (required)",
      "platform_risk_score": "integer (0-100, required)",
      "platform_risk_level": "string (required, enum: LOW | MEDIUM | HIGH | CRITICAL)",
      "posts": [
        {
          "post_content": {
            "text": "string or null (optional)",
            "image_url": "string or null (optional)",
            "video_url": "string or null (optional)"
          },
          "analysis": {
            "risk_score": "integer (0-100, required)",
            "risk_level": "string (required, enum: LOW | MEDIUM | HIGH | CRITICAL)",
            "scores": {
              "toxicity": "float (0-1, required)",
              "threat": "float (0-1, required)",
              "insult": "float (0-1, required)",
              "hate_speech": "float (0-1, required)"
            },
            "context": {
              "category": "string (required)",
              "explanation": "string (required)"
            }
          }
        }
      ]
    }
  ]
}
```

---

## 🎯 Catatan Penting

1. **Prioritaskan XLM-RoBERTa** di atas HateBERT untuk bahasa Indonesia
2. **Human-in-the-loop**: AI hanya screening, keputusan final tetap HR
3. **False positive** dikurangi dengan LLM context analysis
4. **Bahasa gaul Indonesia** dipahami lebih baik oleh XLM-RoBERTa + Qwen
5. **Screenshot/meme** banyak beredar, OCR sangat penting
6. **Komentar & balasan** juga dianalisis, bukan hanya postingan
7. **Gambar & video** diekstrak teksnya dengan OCR/Whisper

---

## 📝 Lisensi & Etika

- **Public Data Only** - Hanya analisis data publik
- **Privacy First** - Tidak menyimpan data pribadi sensitif
- **Fair Recruitment** - Hanya sebagai alat bantu, bukan keputusan mutlak
- **Compliance** - Sesuai UU ITE dan UU Perlindungan Data Pribadi

_Dibuat untuk tim 3 orang - AI Recruitment Risk Analyzer v1.0_

```

```
