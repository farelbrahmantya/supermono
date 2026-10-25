<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LASMONO - Portal Akses Layanan Service Monitoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0b1e38 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px 10px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
            border: none;
        }
        .login-header {
            background-color: #0b1e38;
            color: #ffffff;
            padding: 32px 24px 24px;
            text-align: center;
            position: relative;
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            margin: 0 auto 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .nav-pills-custom {
            background: #f1f5f9;
            padding: 5px;
            border-radius: 12px;
        }
        .nav-pills-custom .nav-link {
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
            border-radius: 9px;
            padding: 10px 16px;
            transition: all 0.2s ease;
        }
        .nav-pills-custom .nav-link.active {
            background-color: #ffffff;
            color: #0b1e38;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .form-label-custom {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control-custom {
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .form-control-custom:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-submit-driver {
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s;
        }
        .btn-submit-driver:hover {
            background: #1d4ed8;
            color: #ffffff;
        }
        .btn-submit-staff {
            background: #0b1e38;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(11, 30, 56, 0.3);
            transition: all 0.2s;
        }
        .btn-submit-staff:hover {
            background: #1e293b;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Header Branding -->
        <div class="login-header">
            <div class="brand-logo">LAS</div>
            <h4 class="fw-bold mb-1">LASMONO</h4>
            <p class="text-light opacity-75 small mb-0">Layanan Service Monitoring Container Yard Depo MTKI</p>
        </div>

        <div class="p-4">
            <!-- Nav Tab Pilihan Role -->
            <ul class="nav nav-pills nav-pills-custom nav-justified mb-4" id="loginTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="driver-tab" data-bs-toggle="pill" data-bs-target="#driver-pane" type="button" role="tab">
                        <i class="fas fa-truck me-1"></i> Portal Supir
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="staff-tab" data-bs-toggle="pill" data-bs-target="#staff-pane" type="button" role="tab">
                        <i class="fas fa-user-shield me-1"></i> Petugas Depo
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="loginTabContent">
                <!-- FORM LOGIN SUPIR -->
                <div class="tab-pane fade show active" id="driver-pane" role="tabpanel">
                    <?php if($this->session->flashdata('error_driver')): ?>
                        <div class="alert alert-danger py-2 px-3 small border-0 rounded-3 mb-3">
                            <i class="fas fa-exclamation-circle me-1"></i> <?= $this->session->flashdata('error_driver'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('auth/process_login_driver'); ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label form-label-custom">Nomor Handphone (Kitir)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-phone"></i></span>
                                <input type="text" name="driver_phone" class="form-control form-control-custom border-start-0" placeholder="Contoh: 08123456789" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label form-label-custom">Nomor Polisi Truk</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-id-card"></i></span>
                                <input type="text" name="license_plate" class="form-control form-control-custom border-start-0 text-uppercase" placeholder="Contoh: B 9123 UQK" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-submit-driver w-100">
                            MASUK ANTREAN <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </form>
                </div>

                <!-- FORM LOGIN PETUGAS (YARDMAN & CONTROL TOWER) -->
                <div class="tab-pane fade" id="staff-pane" role="tabpanel">
                    <?php if($this->session->flashdata('error_staff')): ?>
                        <div class="alert alert-danger py-2 px-3 small border-0 rounded-3 mb-3">
                            <i class="fas fa-exclamation-circle me-1"></i> <?= $this->session->flashdata('error_staff'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('auth/process_login_staff'); ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label form-label-custom">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control form-control-custom border-start-0" placeholder="Masukkan username" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label form-label-custom">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control form-control-custom border-start-0" placeholder="Masukkan password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-submit-staff w-100">
                            LOGIN PETUGAS <i class="fas fa-sign-in-alt ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-light px-4 py-3 text-center border-top">
            <small class="text-muted" style="font-size: 11px;">&copy; <?= date('Y'); ?> Depo Petikemas MTKI. System Active.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>