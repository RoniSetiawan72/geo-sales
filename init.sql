-- ============================================================
-- DATABASE: AI-Powered Geo-Sales Prospecting Tool
-- Versi 1.0 (Final - Docker Ready)
-- ============================================================

-- Catatan: Skrip ini akan otomatis dijalankan saat container PostgreSQL
-- pertama kali dibuat (volume kosong).

-- HAPUS TABEL YANG SUDAH ADA (UNTUK EKSEKUSI ULANG) - HATI-HATI!
DROP TABLE IF EXISTS credit_transactions, tenant_credits, prospects, prospecting_jobs, products, users, tenants, subscription_plans CASCADE;

-- ============================================================
-- 1. TABEL MASTER (PAKET, TENANT, USER, PRODUK)
-- ============================================================

CREATE TABLE subscription_plans (
    id                  SERIAL PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    description         TEXT,
    max_radius_km       INTEGER NOT NULL,
    max_keywords        INTEGER NOT NULL,
    max_prospects_per_job INTEGER NOT NULL,
    credits_per_month   INTEGER NOT NULL,
    price               DECIMAL(10,2) NOT NULL,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tenants (
    id                  SERIAL PRIMARY KEY,
    company_name        VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    phone               VARCHAR(50),
    address             TEXT,
    subscription_plan_id INTEGER REFERENCES subscription_plans(id),
    subscription_start  DATE,
    subscription_end    DATE,
    is_active           BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id                  SERIAL PRIMARY KEY,
    tenant_id           INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    email               VARCHAR(255) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NOT NULL,
    full_name           VARCHAR(255) NOT NULL,
    role                VARCHAR(50) NOT NULL CHECK (role IN ('super_admin', 'tenant_admin', 'sales_user')),
    is_active           BOOLEAN DEFAULT TRUE,
    last_login          TIMESTAMP WITH TIME ZONE,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id                  SERIAL PRIMARY KEY,
    tenant_id           INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    description         TEXT,
    usp                 TEXT,
    target_industries   TEXT[],
    is_active           BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. PROSPECTING JOB (TUGAS PENCARIAN)
-- ============================================================

CREATE TABLE prospecting_jobs (
    id                  BIGSERIAL PRIMARY KEY,
    tenant_id           INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    user_id             INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    product_id          INTEGER REFERENCES products(id) ON DELETE SET NULL,
    product_name        VARCHAR(255) NOT NULL,
    product_description TEXT,
    product_usp         TEXT,
    target_keywords     TEXT[],
    center_lat          DECIMAL(10,8) NOT NULL,
    center_lng          DECIMAL(11,8) NOT NULL,
    radius_meters       INTEGER NOT NULL,
    status              VARCHAR(50) NOT NULL DEFAULT 'pending'
                        CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),
    total_extracted     INTEGER DEFAULT 0,
    total_scored        INTEGER DEFAULT 0,
    credits_used        INTEGER NOT NULL DEFAULT 0,
    error_message       TEXT,
    started_at          TIMESTAMP WITH TIME ZONE,
    completed_at        TIMESTAMP WITH TIME ZONE,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_jobs_tenant_status ON prospecting_jobs(tenant_id, status);
CREATE INDEX idx_jobs_user ON prospecting_jobs(user_id);
CREATE INDEX idx_jobs_created ON prospecting_jobs(created_at DESC);

-- ============================================================
-- 3. PROSPEK (HASIL EKSTRAKSI + AI SCORING)
-- ============================================================

CREATE TABLE prospects (
    id                  BIGSERIAL PRIMARY KEY,
    job_id              BIGINT NOT NULL REFERENCES prospecting_jobs(id) ON DELETE CASCADE,
    business_name       VARCHAR(255) NOT NULL,
    address             TEXT,
    lat                 DECIMAL(10,8),
    lng                 DECIMAL(11,8),
    phone               VARCHAR(50),
    website             VARCHAR(255),
    rating              DECIMAL(3,2),
    reviews_count       INTEGER,
    ai_score            INTEGER CHECK (ai_score BETWEEN 0 AND 100),
    ai_classification   VARCHAR(10) CHECK (ai_classification IN ('Cold', 'Warm', 'Hot')),
    ai_reasoning        TEXT,
    prospect_status     VARCHAR(50) DEFAULT 'new' CHECK (prospect_status IN ('new', 'contacted', 'qualified', 'lost')),
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_prospects_job ON prospects(job_id);
CREATE INDEX idx_prospects_score ON prospects(ai_score DESC);
CREATE INDEX idx_prospects_classification ON prospects(ai_classification);
CREATE INDEX idx_prospects_status ON prospects(prospect_status);

-- ============================================================
-- 4. LOG / EVENT (OPSIONAL)
-- ============================================================

CREATE TABLE job_events (
    id                  BIGSERIAL PRIMARY KEY,
    job_id              BIGINT NOT NULL REFERENCES prospecting_jobs(id) ON DELETE CASCADE,
    event_type          VARCHAR(50) NOT NULL,
    message             TEXT,
    metadata            JSONB,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_events_job ON job_events(job_id);
CREATE INDEX idx_events_created ON job_events(created_at DESC);

-- ============================================================
-- 5. MANAJEMEN KREDIT (UNTUK CARD 6)
-- ============================================================

CREATE TABLE tenant_credits (
    tenant_id           INTEGER PRIMARY KEY REFERENCES tenants(id) ON DELETE CASCADE,
    balance             INTEGER NOT NULL DEFAULT 0,
    last_updated        TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE credit_transactions (
    id                  BIGSERIAL PRIMARY KEY,
    tenant_id           INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    amount              INTEGER NOT NULL,
    description         TEXT,
    reference_id        BIGINT,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 6. FUNGSI UNTUK MEMBUAT JOB DENGAN ACID TRANSACTION (CARD 6)
-- ============================================================

CREATE OR REPLACE FUNCTION create_prospecting_job(
    p_tenant_id INT,
    p_user_id INT,
    p_product_id INT,
    p_product_name VARCHAR,
    p_product_description TEXT,
    p_product_usp TEXT,
    p_keywords TEXT[],
    p_center_lat DECIMAL,
    p_center_lng DECIMAL,
    p_radius_meters INT,
    p_credits_cost INT
)
RETURNS BIGINT
LANGUAGE plpgsql
AS $$
DECLARE
    v_job_id BIGINT;
    v_current_balance INT;
BEGIN
    -- Kunci baris kredit untuk menghindari race condition
    SELECT balance INTO v_current_balance
    FROM tenant_credits
    WHERE tenant_id = p_tenant_id
    FOR UPDATE;

    -- Cek kecukupan saldo
    IF v_current_balance < p_credits_cost THEN
        RAISE EXCEPTION 'Saldo kredit tidak mencukupi (tersisa: %, dibutuhkan: %)', v_current_balance, p_credits_cost;
    END IF;

    -- Kurangi saldo
    UPDATE tenant_credits
    SET balance = balance - p_credits_cost,
        last_updated = CURRENT_TIMESTAMP
    WHERE tenant_id = p_tenant_id;

    -- Catat transaksi debit
    INSERT INTO credit_transactions (tenant_id, amount, description)
    VALUES (p_tenant_id, -p_credits_cost, 'Pembuatan Prospecting Job');

    -- Insert job
    INSERT INTO prospecting_jobs (
        tenant_id, user_id, product_id,
        product_name, product_description, product_usp,
        target_keywords,
        center_lat, center_lng, radius_meters,
        credits_used, status
    ) VALUES (
        p_tenant_id, p_user_id, p_product_id,
        p_product_name, p_product_description, p_product_usp,
        p_keywords,
        p_center_lat, p_center_lng, p_radius_meters,
        p_credits_cost, 'pending'
    )
    RETURNING id INTO v_job_id;

    -- Commit otomatis terjadi jika tidak ada exception
    RETURN v_job_id;
END;
$$;

-- ============================================================
-- 7. SEEDING DATA AWAL (CONTOH PAKET LANGGANAN)
-- ============================================================
INSERT INTO subscription_plans (name, description, max_radius_km, max_keywords, max_prospects_per_job, credits_per_month, price)
VALUES
    ('Starter', 'Paket pemula untuk pencarian area terbatas', 5, 3, 100, 100, 150000),
    ('Pro', 'Paket profesional untuk tim penjualan', 20, 10, 500, 500, 500000),
    ('Enterprise', 'Paket korporasi tanpa batasan', 100, 25, 5000, 2000, 1500000)
ON CONFLICT DO NOTHING;
