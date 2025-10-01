<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_komisija_logged_in']) || !$_SESSION['admin_komisija_logged_in']) {
    wp_die('Nemate dozvolu za pristup ovoj stranici.');
}

$admin_ime = $_SESSION['admin_komisija_ime'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URS Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #0073aa;
            --accent-color: #f59e0b;
            --dark-bg: #1f2937;
            --light-bg: #f8fafc;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            margin: 20px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            overflow: hidden;
            min-height: calc(100vh - 40px);
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #b91c1c 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .admin-nav {
            background: var(--dark-bg);
            padding: 0;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-pills .nav-link {
            color: #d1d5db;
            border-radius: 0;
            padding: 18px 25px;
            border-bottom: 1px solid #374151;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .nav-pills .nav-link:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .nav-pills .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .nav-pills .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
        }
        
        .content-area {
            padding: 30px;
            min-height: 600px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #005a87 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0,115,170,0.2);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--primary-color) 0%, #b91c1c 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 36px;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-danger-custom {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 36px;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            color: white;
        }
        
        /* Action buttons container */
        .d-flex.gap-2 {
            gap: 8px !important;
        }
        
        /* Icon spacing in buttons */
        .btn i {
            pointer-events: none;
        }
        
        /* Sortable table headers */
        .sortable {
            user-select: none;
            transition: background-color 0.3s ease;
        }
        
        .sortable:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .sortable i {
            margin-left: 8px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .sortable:hover i {
            opacity: 1;
        }
        
        .sortable.sorted-asc i:before {
            content: "\f0de"; /* fa-sort-up */
        }
        
        .sortable.sorted-desc i:before {
            content: "\f0dd"; /* fa-sort-down */
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #b91c1c 100%);
            color: white;
            border-bottom: none;
        }
        
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }
        
        /* Enhanced filter styling */
        .card-header.bg-light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .form-label.fw-bold {
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.15);
            transform: translateY(-1px);
        }
        
        .btn-lg {
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary.btn-lg {
            background: linear-gradient(135deg, var(--primary-color) 0%, #b91c1c 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        
        .btn-primary.btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }
        
        .btn-outline-secondary.btn-lg {
            border: 2px solid #6c757d;
            color: #6c757d;
            font-weight: 600;
        }
        
        .btn-outline-secondary.btn-lg:hover {
            background: #6c757d;
            border-color: #6c757d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }
        
        .row.g-3 {
            margin-bottom: 0;
        }
        
        .alert.alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #b8dacd;
            color: #155724;
        }
        
        .alert.alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #f6d55c;
            color: #856404;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
        
        /* Filter Section Enhancements */
        .bg-light {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
            border: 1px solid #e2e8f0;
        }
        
        .bg-light .form-label {
            color: #374151;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }
        
        .bg-light .form-control,
        .bg-light .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .bg-light .form-control:focus,
        .bg-light .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            outline: none;
        }
        
        .bg-light .btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .bg-light .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        @media (max-width: 768px) {
            .admin-header {
                text-align: center;
            }
            
            .admin-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .content-area {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1 class="mb-1"><i class="fas fa-shield-alt"></i> URS Admin Panel</h1>
                <p class="mb-0 opacity-75">Upravljanje operaterima i podacima</p>
            </div>
            <div class="user-info">
                <div><i class="fas fa-user"></i> <?php echo esc_html($admin_ime); ?></div>
                <small class="opacity-75">Administrator</small>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="admin-nav">
            <ul class="nav nav-pills nav-fill" id="adminTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#dashboard" id="dashboard-tab">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#operators" id="operators-tab">
                        <i class="fas fa-users-cog"></i> Operateri (user_komisije)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#users" id="users-tab">
                        <i class="fas fa-users"></i> Korisnici (users_data)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#matches" id="matches-tab">
                        <i class="fas fa-futbol"></i> Utakmice (user_match)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#statistics" id="statistics-tab">
                        <i class="fas fa-chart-bar"></i> Statistike
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Content Area -->
        <div class="tab-content content-area">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3 id="totalOperators">0</h3>
                            <p class="mb-0">Ukupno operatera</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3 id="activeOperators">0</h3>
                            <p class="mb-0">Aktivni operateri</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3 id="totalMatches">0</h3>
                            <p class="mb-0">Ukupno utakmica</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3 id="todayLogins">0</h3>
                            <p class="mb-0">Prijave danas</p>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <h5><i class="fas fa-info-circle"></i> Pregled sistema</h5>
                    <p class="text-muted">Dobro došli u URS Admin Panel. Ovde možete upravljati operaterima, utakmicama i pregledom statistika.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="alert alert-info alert-custom">
                                <h6><i class="fas fa-database"></i> Baza podataka</h6>
                                <p class="mb-0">Server: localhost:3306<br>Database: ursbihba_lara195</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-success alert-custom">
                                <h6><i class="fas fa-check-circle"></i> Status sistema</h6>
                                <p class="mb-0">Sistem je aktivan i funkcionalan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Operators Tab -->
            <div class="tab-pane fade" id="operators">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-users-cog"></i> Upravljanje operaterima (user_komisije)</h5>
                        <div>
                            <button class="btn btn-info me-2" onclick="openPrivilegesModal()">
                                <i class="fas fa-shield-alt"></i> Upravljanje privilegijama
                            </button>
                            <button class="btn btn-admin" onclick="openAddOperatorModal()">
                                <i class="fas fa-plus"></i> Dodaj operatera
                            </button>
                        </div>
                    </div>

                    <!-- Search Section for Operators -->
                    <div class="bg-light p-3 rounded-3 mb-4 shadow-sm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="operatorsSearch" class="form-label fw-bold small">
                                    <i class="fas fa-search me-1"></i>Pretraži operatere (sva polja)
                                </label>
                                <input type="text" class="form-control" id="operatorsSearch"
                                       placeholder="Pretraži po imenu, email-u, korisničkom imenu, ulozi ili privilegijama...">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-fill" onclick="searchOperators()">
                                        <i class="fas fa-search me-1"></i>Pretraži
                                    </button>
                                    <button class="btn btn-outline-secondary flex-fill" onclick="clearOperatorsSearch()">
                                        <i class="fas fa-eraser me-1"></i>Očisti
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div id="operatorsSearchResults" class="alert alert-info d-none py-2" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="operatorsSearchResultsText"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="operatorsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Korisničko ime</th>
                                    <th>Ime i prezime</th>
                                    <th>Email</th>
                                    <th>Uloga</th>
                                    <th>Tip komisije</th>
                                    <th style="min-width: 200px;">Osnovne privilegije</th>
                                    <th style="min-width: 250px;">Poljne privilegije</th>
                                    <th>Status</th>
                                    <th>Akcije</th>
                                </tr>
                            </thead>
                            <tbody id="operatorsTableBody">
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Učitavam podatke...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Users Data Tab -->
            <div class="tab-pane fade" id="users">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-users"></i> Upravljanje korisnicima (users_data)</h5>
                        <button class="btn btn-admin" onclick="openAddUserModal()">
                            <i class="fas fa-plus"></i> Dodaj korisnika
                        </button>
                    </div>

                    <!-- Search Section for Users -->
                    <div class="bg-light p-3 rounded-3 mb-4 shadow-sm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="usersSearch" class="form-label fw-bold small">
                                    <i class="fas fa-search me-1"></i>Pretraži korisnike (sva polja)
                                </label>
                                <input type="text" class="form-control" id="usersSearch"
                                       placeholder="Pretraži po imenu, prezimenu, email-u, gradu, mobitel-u, ulozi ili sezoni...">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-fill" onclick="searchUsers()">
                                        <i class="fas fa-search me-1"></i>Pretraži
                                    </button>
                                    <button class="btn btn-outline-secondary flex-fill" onclick="clearUsersSearch()">
                                        <i class="fas fa-eraser me-1"></i>Očisti
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div id="usersSearchResults" class="alert alert-info d-none py-2" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="usersSearchResultsText"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="sortable" onclick="sortUsers('id')" style="cursor: pointer;">
                                        ID <i class="fas fa-sort" id="sort-user-id"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('USER_ID')" style="cursor: pointer;">
                                        User ID <i class="fas fa-sort" id="sort-user-USER_ID"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('Ime')" style="cursor: pointer;">
                                        Ime <i class="fas fa-sort" id="sort-user-Ime"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('Prezime')" style="cursor: pointer;">
                                        Prezime <i class="fas fa-sort" id="sort-user-Prezime"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('EMAIL')" style="cursor: pointer;">
                                        Email <i class="fas fa-sort" id="sort-user-EMAIL"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('MOBITEL')" style="cursor: pointer;">
                                        Mobitel <i class="fas fa-sort" id="sort-user-MOBITEL"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('GRAD')" style="cursor: pointer;">
                                        Grad <i class="fas fa-sort" id="sort-user-GRAD"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('ULOGA_ID')" style="cursor: pointer;">
                                        Uloga <i class="fas fa-sort" id="sort-user-ULOGA_ID"></i>
                                    </th>
                                    <th class="sortable" onclick="sortUsers('SEZONA')" style="cursor: pointer;">
                                        Sezona <i class="fas fa-sort" id="sort-user-SEZONA"></i>
                                    </th>
                                    <th>Akcije</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Učitavam podatke...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Matches Tab -->
            <div class="tab-pane fade" id="matches">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-futbol"></i> Upravljanje utakmicama (user_match)</h5>
                        <button class="btn btn-admin" onclick="openAddMatchModal()">
                            <i class="fas fa-plus"></i> Dodaj utakmicu
                        </button>
                    </div>

                    <!-- Universal Search Section for Matches -->
                    <div class="bg-primary p-3 rounded-3 mb-4 shadow-sm text-white">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="matchesUniversalSearch" class="form-label fw-bold small text-white">
                                    <i class="fas fa-search me-1"></i>Univerzalna pretraga (sva polja uključujući ID Utakmice)
                                </label>
                                <input type="text" class="form-control" id="matchesUniversalSearch"
                                       placeholder="Pretraži po ID utakmice, imenu, nazivu utakmice, ligi, kolu, sezoni, ulozi...">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light flex-fill" onclick="searchMatchesUniversal()">
                                        <i class="fas fa-search me-1"></i>Pretraži sve
                                    </button>
                                    <button class="btn btn-outline-light flex-fill" onclick="clearMatchesUniversalSearch()">
                                        <i class="fas fa-eraser me-1"></i>Očisti
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div id="matchesUniversalSearchResults" class="alert alert-light d-none py-2" role="alert">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>
                                    <span id="matchesUniversalSearchResultsText" class="text-dark"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter sekcija -->
                    <div class="bg-light p-4 rounded-3 mb-4 shadow-sm">
                        <div class="mb-3">
                            <h5 class="text-primary mb-0"><i class="fas fa-filter me-2"></i>Filtri za pretragu utakmica</h5>
                        </div>
                        
                        <!-- Prvi red filtera -->
                        <div class="row g-3 mb-3">
                            <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4">
                                <label for="filterMatchId" class="form-label fw-bold small">ID Utakmice</label>
                                <input type="number" class="form-control form-control-sm" id="filterMatchId" placeholder="Unesi ID utakmice">
                            </div>
                            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6">
                                <label for="filterMatchUser" class="form-label fw-bold small">Ime/Prezime korisnika</label>
                                <input type="text" class="form-control form-control-sm" id="filterMatchUser" placeholder="Pretraži po imenu/prezimenu">
                            </div>
                            <div class="col-xxl-3 col-xl-3 col-lg-5 col-md-6">
                                <label for="filterMatchUtakmica" class="form-label fw-bold small">Naziv utakmice</label>
                                <input type="text" class="form-control form-control-sm" id="filterMatchUtakmica" placeholder="Pretraži po nazivu utakmice">
                            </div>
                            <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4">
                                <label for="filterMatchLiga" class="form-label fw-bold small">Liga</label>
                                <select class="form-select form-select-sm" id="filterMatchLiga">
                                    <option value="">Sve lige</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4">
                                <label for="filterMatchSezona" class="form-label fw-bold small">Sezona</label>
                                <select class="form-select form-select-sm" id="filterMatchSezona">
                                    <option value="">Sve sezone</option>
                                </select>
                            </div>
                        </div>

                        <!-- Drugi red filtera -->
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4">
                                <label for="filterMatchKolo" class="form-label fw-bold small">Kolo</label>
                                <select class="form-select form-select-sm" id="filterMatchKolo">
                                    <option value="">Sva kola</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4">
                                <label for="filterMatchUloga" class="form-label fw-bold small">Uloga</label>
                                <select class="form-select form-select-sm" id="filterMatchUloga">
                                    <option value="">Sve uloge</option>
                                    <option value="Sudija">Sudija</option>
                                    <option value="Delegat">Delegat</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4">
                                <label for="filterMatchStatus" class="form-label fw-bold small">Status</label>
                                <select class="form-select form-select-sm" id="filterMatchStatus">
                                    <option value="">Svi statusi</option>
                                    <option value="1">Aktivan</option>
                                    <option value="0">Neaktivan</option>
                                </select>
                            </div>
                            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary btn-sm flex-fill" onclick="applyMatchFilters()">
                                        <i class="fas fa-search me-1"></i>Primijeni filtere
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="clearMatchFilters()">
                                        <i class="fas fa-eraser me-1"></i>Očisti sve
                                    </button>
                                </div>
                            </div>
                            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12">
                                <!-- Rezultat pretrage -->
                                <div id="filterResults" class="alert alert-info d-none mb-0 py-2" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="filterResultsText"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <label for="recordsPerPage" class="form-label mb-0 fw-bold">Zapisa po stranici:</label>
                            <select class="form-select" id="recordsPerPage" style="width: auto;" onchange="changeRecordsPerPage()">
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                                <option value="all">Sve</option>
                            </select>
                        </div>
                        <div id="paginationInfo" class="text-muted">
                            <!-- Pagination info will be inserted here -->
                        </div>
                        <div id="paginationControls">
                            <!-- Pagination buttons will be inserted here -->
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="matchesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="sortable" onclick="sortMatches('id')" style="cursor: pointer;">
                                        ID <i class="fas fa-sort" id="sort-id"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('id_utakmice')" style="cursor: pointer;">
                                        ID Utakmice <i class="fas fa-sort" id="sort-id_utakmice"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('user_full_name')" style="cursor: pointer;">
                                        Ime i prezime <i class="fas fa-sort" id="sort-user_full_name"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('utakmica')" style="cursor: pointer;">
                                        Utakmica <i class="fas fa-sort" id="sort-utakmica"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('liga')" style="cursor: pointer;">
                                        Liga <i class="fas fa-sort" id="sort-liga"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('kolo')" style="cursor: pointer;">
                                        Kolo <i class="fas fa-sort" id="sort-kolo"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('sezona')" style="cursor: pointer;">
                                        Sezona <i class="fas fa-sort" id="sort-sezona"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('uloga')" style="cursor: pointer;">
                                        Uloga <i class="fas fa-sort" id="sort-uloga"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('tarifa')" style="cursor: pointer;">
                                        Tarifa <i class="fas fa-sort" id="sort-tarifa"></i>
                                    </th>
                                    <th class="sortable" onclick="sortMatches('status')" style="cursor: pointer;">
                                        Status <i class="fas fa-sort" id="sort-status"></i>
                                    </th>
                                    <th>Akcije</th>
                                </tr>
                            </thead>
                            <tbody id="matchesTableBody">
                                <tr>
                                    <td colspan="11" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Učitavam podatke...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bottom Pagination Controls -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="bottomPaginationInfo" class="text-muted">
                            <!-- Bottom pagination info -->
                        </div>
                        <div id="bottomPaginationControls">
                            <!-- Bottom pagination buttons -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Tab -->
            <div class="tab-pane fade" id="statistics">
                <div class="table-container">
                    <h5><i class="fas fa-chart-bar"></i> Statistike sistema</h5>
                    <p class="text-muted">Ovde će biti prikazane detaljne statistike</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Statistike će biti implementirane u sledećoj verziji
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Operator Modal -->
    <div class="modal fade" id="operatorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="operatorModalTitle">
                        <i class="fas fa-user-plus"></i> Dodaj operatera
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="operatorForm">
                        <input type="hidden" id="operatorId" name="operatorId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Korisničko ime *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Lozinka *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ime" class="form-label">Ime *</label>
                                <input type="text" class="form-control" id="ime" name="ime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prezime" class="form-label">Prezime *</label>
                                <input type="text" class="form-control" id="prezime" name="prezime" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefon" class="form-label">Telefon</label>
                                <input type="text" class="form-control" id="telefon" name="telefon">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="uloga" class="form-label">Uloga *</label>
                                <select class="form-select" id="uloga" name="uloga" required>
                                    <option value="">Izaberite ulogu</option>
                                    <option value="1">Admin</option>
                                    <option value="2">Komisija</option>
                                    <option value="3">Ostalo</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tipKomisije" class="form-label">Tip komisije</label>
                                <select class="form-select" id="tipKomisije" name="tipKomisije">
                                    <option value="">Nije komisija</option>
                                    <option value="strucno-sudijska">Stručno sudijska</option>
                                    <option value="disciplinska">Disciplinska</option>
                                    <option value="zalbena">Žalbena</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="aktivan" name="aktivan" checked>
                                <label class="form-check-label" for="aktivan">
                                    Aktivan
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                    <button type="button" class="btn btn-admin" onclick="saveOperator()">
                        <i class="fas fa-save"></i> Sačuvaj
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privileges Management Modal -->
    <div class="modal fade" id="privilegesModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt"></i> Upravljanje privilegijama komisija
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Ovdje možete postaviti privilegije za svaku komisiju/ulogu. Privilegije određuju što operateri mogu raditi sa utakmicama.
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="fas fa-users-cog me-2"></i>Komisije/Uloge</h6>
                            <div class="list-group" id="privilegeRolesList">
                                <div class="text-center p-3">
                                    <i class="fas fa-spinner fa-spin"></i> Učitavam komisije...
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6><i class="fas fa-shield-alt me-2"></i>Privilegije za <span id="selectedRoleName">-</span></h6>
                            <div id="privilegesForm" style="display: none;">
                        <div class="row">
                            <!-- Osnovne privilegije -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Osnovne privilegije</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_view">
                                                    <label class="form-check-label" for="privilege_view">
                                                        <i class="fas fa-eye text-info me-2"></i>Pregled utakmica
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_create">
                                                    <label class="form-check-label" for="privilege_create">
                                                        <i class="fas fa-plus text-success me-2"></i>Dodavanje novih utakmica
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_edit">
                                                    <label class="form-check-label" for="privilege_edit">
                                                        <i class="fas fa-edit text-warning me-2"></i>Uređivanje utakmica
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_delete">
                                                    <label class="form-check-label" for="privilege_delete">
                                                        <i class="fas fa-trash text-danger me-2"></i>Brisanje utakmica
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_export">
                                                    <label class="form-check-label" for="privilege_export">
                                                        <i class="fas fa-download text-primary me-2"></i>Izvoz podataka
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="privilege_import">
                                                    <label class="form-check-label" for="privilege_import">
                                                        <i class="fas fa-upload text-secondary me-2"></i>Uvoz podataka
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Privilegije za polja -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Privilegije za polja</h6>
                                        <small class="text-muted">Kontroliše koja polja korisnik može editovati</small>
                                    </div>
                                    <div class="card-body">
                                        <!-- Grupa 1: Osnovni podaci -->
                                        <div class="mb-3">
                                            <small class="text-muted fw-bold d-block mb-2">
                                                <i class="fas fa-info-circle me-1"></i>Osnovni podaci utakmice
                                            </small>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_user_id">
                                                        <label class="form-check-label" for="field_user_id">
                                                            <i class="fas fa-user text-primary me-2"></i>Korisnik
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_liga">
                                                        <label class="form-check-label" for="field_liga">
                                                            <i class="fas fa-trophy text-warning me-2"></i>Liga
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_kolo">
                                                        <label class="form-check-label" for="field_kolo">
                                                            <i class="fas fa-list-ol text-info me-2"></i>Kolo
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_sezona">
                                                        <label class="form-check-label" for="field_sezona">
                                                            <i class="fas fa-calendar text-secondary me-2"></i>Sezona
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_utakmica">
                                                        <label class="form-check-label" for="field_utakmica">
                                                            <i class="fas fa-futbol text-success me-2"></i>Utakmica
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_uloga">
                                                        <label class="form-check-label" for="field_uloga">
                                                            <i class="fas fa-user-tag text-primary me-2"></i>Uloga
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grupa 2: Finansije i logistika -->
                                        <div class="mb-3">
                                            <small class="text-muted fw-bold d-block mb-2">
                                                <i class="fas fa-euro-sign me-1"></i>Finansije i logistika
                                            </small>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_tarifa">
                                                        <label class="form-check-label" for="field_tarifa">
                                                            <i class="fas fa-euro-sign text-success me-2"></i>Tarifa
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_putni_troskovi">
                                                        <label class="form-check-label" for="field_putni_troskovi">
                                                            <i class="fas fa-car text-warning me-2"></i>Putni troškovi
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grupa 3: Sistem i napomene -->
                                        <div class="mb-3">
                                            <small class="text-muted fw-bold d-block mb-2">
                                                <i class="fas fa-cogs me-1"></i>Sistem i napomene
                                            </small>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_datum_obavjesti">
                                                        <label class="form-check-label" for="field_datum_obavjesti">
                                                            <i class="fas fa-bell text-info me-2"></i>Datum obavještenja
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_komentar">
                                                        <label class="form-check-label" for="field_komentar">
                                                            <i class="fas fa-comment text-secondary me-2"></i>Komentar
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_status">
                                                        <label class="form-check-label" for="field_status">
                                                            <i class="fas fa-toggle-on text-success me-2"></i>Status
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="field_id_utakmice">
                                                        <label class="form-check-label" for="field_id_utakmice">
                                                            <i class="fas fa-hashtag text-primary me-2"></i>ID utakmice
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ograničenja -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Dodatna ograničenja</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="privilege_own_only">
                                            <label class="form-check-label" for="privilege_own_only">
                                                <i class="fas fa-user-lock text-warning me-2"></i>Može uređivati samo svoje utakmice
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="privilege_current_season_only">
                                            <label class="form-check-label" for="privilege_current_season_only">
                                                <i class="fas fa-calendar-alt text-info me-2"></i>Ograničeno na trenutnu sezonu
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-success" onclick="savePrivileges()">
                                <i class="fas fa-save me-2"></i>Sačuvaj privilegije
                            </button>
                        </div>
                    </div>
                            <div id="noRoleSelected" class="text-center text-muted p-4">
                                <i class="fas fa-hand-pointer fa-2x mb-3"></i>
                                <p>Odaberite komisiju/ulogu sa lijeve strane da postavite privilegije</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="fas fa-user-plus"></i> Dodaj korisnika
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userUserId" class="form-label">User ID *</label>
                                <input type="text" class="form-control" id="userUserId" name="USER_ID" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="userPassword" name="Passw">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userUsername" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="userUsername" name="Username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <!-- Spacer column -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userIme" class="form-label">Ime *</label>
                                <input type="text" class="form-control" id="userIme" name="Ime" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userPrezime" class="form-label">Prezime *</label>
                                <input type="text" class="form-control" id="userPrezime" name="Prezime" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="userEmail" name="EMAIL">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userMobitel" class="form-label">Mobitel</label>
                                <input type="text" class="form-control" id="userMobitel" name="MOBITEL">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="userAdresa" class="form-label">Adresa</label>
                                <input type="text" class="form-control" id="userAdresa" name="ADRESA">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userGrad" class="form-label">Grad</label>
                                <input type="text" class="form-control" id="userGrad" name="GRAD">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userBanka" class="form-label">Banka</label>
                                <input type="text" class="form-control" id="userBanka" name="BANKA">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userRacun" class="form-label">Račun</label>
                                <input type="text" class="form-control" id="userRacun" name="RACUN">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userPozivNaBr" class="form-label">Poziv na broj</label>
                                <input type="text" class="form-control" id="userPozivNaBr" name="POZIV_NA_BR">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="userZbor" class="form-label">Zbor</label>
                                <input type="text" class="form-control" id="userZbor" name="ZBOR">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="userKantonalniSavez" class="form-label">Kantonalni savez</label>
                                <input type="text" class="form-control" id="userKantonalniSavez" name="KANTONALNI_SAVEZ">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="userUlogaId" class="form-label">Uloga ID *</label>
                                <select class="form-control" id="userUlogaId" name="ULOGA_ID" required>
                                    <option value="">Izaberite ulogu</option>
                                    <option value="1">Sudija</option>
                                    <option value="2">Delegat</option>
                                    <option value="3">Instruktor</option>
                                    <option value="4">Ostalo</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="userIznosClanarine" class="form-label">Iznos članarine</label>
                                <input type="number" step="0.01" class="form-control" id="userIznosClanarine" name="IZNOS_CLANARINE" value="0.00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="userSezona" class="form-label">Sezona</label>
                                <input type="text" class="form-control" id="userSezona" name="SEZONA" value="2025">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                    <button type="button" class="btn btn-admin" onclick="saveUser()">
                        <i class="fas fa-save"></i> Sačuvaj
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Match Modal -->
    <div class="modal fade" id="matchModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="matchModalTitle">
                        <i class="fas fa-futbol"></i> Dodaj utakmicu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="matchForm">
                        <input type="hidden" id="matchId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="matchClan" class="form-label">Korisnik *</label>
                                <select class="form-control" id="matchClan" name="clan" required>
                                    <option value="">Izaberite korisnika...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="matchIdUtakmice" class="form-label">ID Utakmice *</label>
                                <input type="number" class="form-control" id="matchIdUtakmice" name="id_utakmice" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="matchUtakmica" class="form-label">Utakmica *</label>
                                <textarea class="form-control" id="matchUtakmica" name="utakmica" placeholder="Opis utakmice" rows="3" required></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="matchLiga" class="form-label">Liga *</label>
                                <input type="text" class="form-control" id="matchLiga" name="liga" placeholder="EHF, SEHA, itd." required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchKolo" class="form-label">Kolo/Nominacija *</label>
                                <input type="text" class="form-control" id="matchKolo" name="kolo" placeholder="Nominacija, kolo" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchSezona" class="form-label">Sezona *</label>
                                <input type="text" class="form-control" id="matchSezona" name="sezona" placeholder="2024/2025" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="matchTarifa" class="form-label">Tarifa</label>
                                <input type="number" class="form-control" id="matchTarifa" name="tarifa" step="0.01" placeholder="19.00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchOcjena" class="form-label">Ocjena</label>
                                <input type="number" class="form-control" id="matchOcjena" name="ocjena" min="0" placeholder="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchStatus" class="form-label">Status</label>
                                <input type="number" class="form-control" id="matchStatus" name="status" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="matchUloga" class="form-label">Uloga *</label>
                                <select class="form-control" id="matchUloga" name="uloga" required>
                                    <option value="">Izaberite ulogu</option>
                                    <option value="Sudija">Sudija</option>
                                    <option value="Delegat">Delegat</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchDatumObavjesti" class="form-label">Datum obavještenja</label>
                                <input type="date" class="form-control" id="matchDatumObavjesti" name="datum_obavjesti">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchStatusSsk" class="form-label">Status SSK</label>
                                <input type="number" class="form-control" id="matchStatusSsk" name="status_ssk" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="matchSuspendovan" class="form-label">Suspendovan</label>
                                <select class="form-control" id="matchSuspendovan" name="suspendovan">
                                    <option value="0">Ne</option>
                                    <option value="1">Da</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchKomisija" class="form-label">Komisija</label>
                                <select class="form-control" id="matchKomisija" name="komisija">
                                    <option value="">Sve komisije</option>
                                    <!-- Opcije se učitavaju dinamički preko AJAX -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matchKomentar" class="form-label">Komentar</label>
                                <textarea class="form-control" id="matchKomentar" name="komentar" rows="3" placeholder="Tekstualni komentar"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                    <button type="button" class="btn btn-admin" onclick="saveMatch()">
                        <i class="fas fa-save"></i> Sačuvaj
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="text-center text-white">
            <div class="spinner mb-3"></div>
            <p>Obrađujem zahtev...</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Notification function
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.admin-notification');
            existingNotifications.forEach(n => n.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `admin-notification alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 500px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            
            notification.innerHTML = `
                <strong>${type === 'error' ? '⚠️ Greška:' : type === 'success' ? '✅ Uspjeh:' : 'ℹ️ Info:'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Global variables
        let operatorsData = [];
        let usersData = [];
        let matchesData = [];
        let allMatches = []; // For pagination and filtering
        
        // Pagination variables
        let matchesPagination = {
            currentPage: 1,
            recordsPerPage: 50,
            totalRecords: 0,
            totalPages: 0
        };
        
        // Sorting variables
        let matchesSorting = {
            column: 'id',
            direction: 'desc'
        };
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            loadOperators();
            loadUsers();    // Učitaj korisnike odmah na početku
            loadStatistics();
            loadKomisije(); // Učitaj komisije za dropdown
            
            // Tab change event
            document.querySelectorAll('#adminTabs .nav-link').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(e) {
                    const target = e.target.getAttribute('href');
                    if (target === '#operators') {
                        loadOperators();
                    } else if (target === '#users') {
                        loadUsers();
                    } else if (target === '#matches') {
                        loadMatches();
                    }
                });
            });
            
            // Add event listeners for match filters (after DOM is loaded)
            setTimeout(() => {
                const filterElements = [
                    'filterMatchId', 'filterMatchUser', 'filterMatchUtakmica', 
                    'filterMatchLiga', 'filterMatchKolo', 'filterMatchSezona',
                    'filterMatchUloga', 'filterMatchStatus'
                ];
                
                filterElements.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        if (element.tagName === 'SELECT') {
                            element.addEventListener('change', applyMatchFilters);
                        } else {
                            element.addEventListener('input', debounce(applyMatchFilters, 300));
                        }
                    }
                });
            }, 100);
        });
        
        // Debounce function for input fields
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Load operators from database
        function loadOperators() {
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_operators&nonce=<?php echo wp_create_nonce('admin_operators_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    operatorsData = data.data;
                    originalOperators = [...data.data]; // Store original data for search
                    filteredOperators = [...data.data];
                    displayOperators(operatorsData);
                } else {
                    showAlert('Greška pri učitavanju operatera: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri učitavanju operatera', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Display operators in table
        function displayOperators(operators) {
            const tbody = document.getElementById('operatorsTableBody');
            
            if (operators.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">Nema operatera u bazi</td></tr>';
                return;
            }
            
            tbody.innerHTML = operators.map(operator => {
                const ulogaText = operator.uloga == 1 ? 'Admin' : operator.uloga == 2 ? 'Komisija' : 'Ostalo';
                const statusBadge = operator.aktivan == 1 ? 
                    '<span class="badge bg-success">Aktivan</span>' : 
                    '<span class="badge bg-danger">Neaktivan</span>';
                
                // Pripremi osnovne privilegije
                const basicPrivileges = [];
                if (operator.privilege_view) basicPrivileges.push('Pregled');
                if (operator.privilege_create) basicPrivileges.push('Dodavanje');
                if (operator.privilege_edit) basicPrivileges.push('Uređivanje');
                if (operator.privilege_delete) basicPrivileges.push('Brisanje');
                if (operator.privilege_export) basicPrivileges.push('Izvoz');
                if (operator.privilege_import) basicPrivileges.push('Uvoz');
                
                const basicPrivText = basicPrivileges.length > 0 ? basicPrivileges.join(', ') : 'Nema';
                
                // Pripremi poljne privilegije
                const fieldPrivileges = [];
                if (operator.field_tarifa) fieldPrivileges.push('Tarifa');
                if (operator.field_user_id) fieldPrivileges.push('Korisnik');
                if (operator.field_liga) fieldPrivileges.push('Liga');
                if (operator.field_kolo) fieldPrivileges.push('Kolo');
                if (operator.field_sezona) fieldPrivileges.push('Sezona');
                if (operator.field_utakmica) fieldPrivileges.push('Utakmica');
                if (operator.field_uloga) fieldPrivileges.push('Uloga');
                if (operator.field_datum_obavjesti) fieldPrivileges.push('Datum');
                if (operator.field_putni_troskovi) fieldPrivileges.push('Putni troškovi');
                if (operator.field_komentar) fieldPrivileges.push('Komentar');
                if (operator.field_status) fieldPrivileges.push('Status');
                if (operator.field_id_utakmice) fieldPrivileges.push('ID utakmice');
                
                const fieldPrivText = fieldPrivileges.length > 0 ? fieldPrivileges.join(', ') : 'Nema';
                
                // Pripremi ograničenja
                const restrictions = [];
                if (operator.privilege_own_only) restrictions.push('Samo svoje utakmice');
                if (operator.privilege_current_season_only) restrictions.push('Trenutna sezona');
                const restrictionsDisplay = restrictions.length > 0 ? restrictions.join(', ') : 'Bez ograničenja';
                
                return `
                    <tr>
                        <td>${operator.id}</td>
                        <td>${operator.username}</td>
                        <td>${operator.ime} ${operator.prezime}</td>
                        <td>${operator.email || '-'}</td>
                        <td><span class="badge bg-primary">${ulogaText}</span></td>
                        <td>${operator.tip_komisije || '-'}</td>
                        <td>
                            <small class="text-primary" title="${basicPrivText}">${basicPrivText.length > 30 ? basicPrivText.substring(0, 30) + '...' : basicPrivText}</small>
                            ${restrictionsDisplay !== 'Bez ograničenja' ? 
                                `<br><small class="text-warning" title="${restrictionsDisplay}">${restrictionsDisplay.length > 20 ? restrictionsDisplay.substring(0, 20) + '...' : restrictionsDisplay}</small>` : ''}
                        </td>
                        <td>
                            <small class="text-success" title="${fieldPrivText}">${fieldPrivText.length > 35 ? fieldPrivText.substring(0, 35) + '...' : fieldPrivText}</small>
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-info" onclick="editOperatorPrivileges('${operator.uloga}', '${operator.tip_komisije}')" title="Upravlja privilegijama">
                                    <i class="fas fa-shield-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-success-custom" onclick="editOperator(${operator.id})" title="Izmeni operatera">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger-custom" onclick="deleteOperator(${operator.id})" title="Obriši operatera">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Load statistics
        function loadStatistics() {
            // This would load actual statistics from database
            // For now, using placeholder values
            document.getElementById('totalOperators').textContent = operatorsData.length || '0';
            document.getElementById('activeOperators').textContent = operatorsData.filter(op => op.aktivan == 1).length || '0';
            document.getElementById('totalMatches').textContent = '0';
            document.getElementById('todayLogins').textContent = '0';
        }
        
        // Load komisije from database for dropdown
        function loadKomisije() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_komisije'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateKomisijeDropdown(data.data);
                } else {
                    console.error('Greška pri učitavanju komisija:', data.data);
                }
            })
            .catch(error => {
                console.error('Error loading komisije:', error);
            });
        }
        
        // Populate komisije dropdown
        function populateKomisijeDropdown(komisije) {
            const select = document.getElementById('matchKomisija');
            if (!select) return;
            
            // Očisti postojeće opcije osim prve (Sve komisije)
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            // Dodaj nove opcije
            komisije.forEach(komisija => {
                const option = document.createElement('option');
                option.value = komisija.value;
                option.textContent = komisija.label;
                select.appendChild(option);
            });
        }
        
        // Open add operator modal
        
        // Privileges Management Functions
        let selectedRoleId = null;
        
        function openPrivilegesModal() {
            loadRolesList();
            new bootstrap.Modal(document.getElementById('privilegesModal')).show();
        }
        
        function loadRolesList() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_roles_for_privileges&nonce=<?php echo wp_create_nonce('admin_privileges_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRolesList(data.data);
                } else {
                    showNotification('Greška pri učitavanju komisija: ' + data.data, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Greška pri učitavanju komisija', 'error');
            });
        }
        
        function displayRolesList(roles) {
            const container = document.getElementById('privilegeRolesList');
            if (roles.length === 0) {
                container.innerHTML = '<div class="text-center p-3 text-muted">Nema definiranih komisija</div>';
                return;
            }
            
            let html = '';
            roles.forEach(role => {
                html += `
                    <a href="#" class="list-group-item list-group-item-action" onclick="selectRole(${role.id}, '${role.uloga}', '${role.tip_komisije}')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${role.uloga}</h6>
                            <small class="text-muted">${role.tip_komisije}</small>
                        </div>
                        <p class="mb-1 small text-muted">Operateri: ${role.operater_count || 0}</p>
                    </a>
                `;
            });
            container.innerHTML = html;
        }
        
        function selectRole(roleId, roleName, roleType) {
            selectedRoleId = roleId;
            
            // Update active state
            document.querySelectorAll('#privilegeRolesList .list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.list-group-item').classList.add('active');
            
            // Update form header
            document.getElementById('selectedRoleName').textContent = `${roleName} (${roleType})`;
            
            // Show form and hide "no selection" message
            document.getElementById('privilegesForm').style.display = 'block';
            document.getElementById('noRoleSelected').style.display = 'none';
            
            // Load current privileges for this role
            loadRolePrivileges(roleId);
        }
        
        function loadRolePrivileges(roleId) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=load_role_privileges&role_id=${roleId}&nonce=<?php echo wp_create_nonce('admin_privileges_nonce'); ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populatePrivilegesForm(data.data);
                } else {
                    showNotification('Greška pri učitavanju privilegija: ' + data.data, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Greška pri učitavanju privilegija', 'error');
            });
        }
        
        function populatePrivilegesForm(privileges) {
            // Set all checkboxes based on loaded privileges
            const checkboxes = [
                'privilege_view', 'privilege_create', 'privilege_edit', 
                'privilege_delete', 'privilege_export', 'privilege_import',
                'privilege_own_only', 'privilege_current_season_only',
                // Field privileges
                'field_tarifa', 'field_user_id', 'field_liga', 'field_kolo',
                'field_sezona', 'field_utakmica', 'field_uloga', 'field_datum_obavjesti',
                'field_putni_troskovi', 'field_komentar', 'field_status', 'field_id_utakmice'
            ];
            
            checkboxes.forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    const privilegeName = checkboxId.replace('privilege_', '').replace('field_', '');
                    checkbox.checked = privileges[privilegeName] === '1' || privileges[privilegeName] === true;
                }
            });
        }
        
        function savePrivileges() {
            if (!selectedRoleId) {
                showNotification('Molimo odaberite komisiju', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_role_privileges');
            formData.append('role_id', selectedRoleId);
            formData.append('nonce', '<?php echo wp_create_nonce('admin_privileges_nonce'); ?>');
            
            // Collect all privilege values
            const checkboxes = [
                'privilege_view', 'privilege_create', 'privilege_edit', 
                'privilege_delete', 'privilege_export', 'privilege_import',
                'privilege_own_only', 'privilege_current_season_only',
                // Field privileges
                'field_tarifa', 'field_user_id', 'field_liga', 'field_kolo',
                'field_sezona', 'field_utakmica', 'field_uloga', 'field_datum_obavjesti',
                'field_putni_troskovi', 'field_komentar', 'field_status', 'field_id_utakmice'
            ];

            // Debug: log what we're sending
            console.log('Sending data for role_id:', selectedRoleId);
            
            checkboxes.forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    const privilegeName = checkboxId.replace('privilege_', '').replace('field_', '');
                    const value = checkbox.checked ? '1' : '0';
                    formData.append(privilegeName, value);
                    console.log(`${privilegeName}: ${value}`);
                }
            });
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get text first to see raw response
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                
                if (text.includes('There has been a critical error')) {
                    showNotification('WordPress critical error detected. Check server logs.', 'error');
                    return;
                }
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showNotification('Privilegije su uspješno sačuvane!', 'success');
                        // Refresh operators table to show updated privileges
                        loadOperators();
                    } else {
                        showNotification('Greška pri čuvanju privilegija: ' + data.data, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Problematic text:', text.substring(0, 200));
                    showNotification('Server response error: ' + text.substring(0, 100), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Greška pri čuvanju privilegija', 'error');
            });
        }

        function editOperatorPrivileges(uloga, tipKomisije) {
            // Open privileges modal and select the role automatically
            openPrivilegesModal();
            
            // Wait for modal to load and then auto-select the role
            setTimeout(() => {
                const roleItems = document.querySelectorAll('#privilegeRolesList .list-group-item');
                roleItems.forEach(item => {
                    const roleText = item.textContent.trim();
                    if (roleText.includes(uloga) && roleText.includes(tipKomisije)) {
                        item.click();
                    }
                });
            }, 500);
        }

        function openAddOperatorModal() {
            document.getElementById('operatorModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Dodaj operatera';
            document.getElementById('operatorForm').reset();
            document.getElementById('operatorId').value = '';
            document.getElementById('password').required = true;
            
            const modal = new bootstrap.Modal(document.getElementById('operatorModal'));
            modal.show();
        }
        
        // Edit operator
        function editOperator(id) {
            const operator = operatorsData.find(op => op.id == id);
            if (!operator) return;
            
            document.getElementById('operatorModalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Izmeni operatera';
            document.getElementById('operatorId').value = operator.id;
            document.getElementById('username').value = operator.username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('ime').value = operator.ime;
            document.getElementById('prezime').value = operator.prezime;
            document.getElementById('email').value = operator.email || '';
            document.getElementById('telefon').value = operator.telefon || '';
            document.getElementById('uloga').value = operator.uloga;
            document.getElementById('tipKomisije').value = operator.tip_komisije || '';
            document.getElementById('aktivan').checked = operator.aktivan == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('operatorModal'));
            modal.show();
        }
        
        // Save operator (add or edit)
        function saveOperator() {
            const form = document.getElementById('operatorForm');
            const formData = new FormData(form);
            
            const operatorId = document.getElementById('operatorId').value;
            const action = operatorId ? 'update_operator' : 'add_operator';
            
            formData.append('action', action);
            formData.append('nonce', '<?php echo wp_create_nonce('admin_operators_nonce'); ?>');
            formData.append('aktivan', document.getElementById('aktivan').checked ? 1 : 0);
            
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('operatorModal')).hide();
                    loadOperators();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri čuvanju operatera', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Delete operator
        function deleteOperator(id) {
            const operator = operatorsData.find(op => op.id == id);
            if (!operator) return;
            
            if (!confirm(`Da li ste sigurni da želite da obrišete operatera "${operator.ime} ${operator.prezime}"?`)) {
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'delete_operator');
            formData.append('nonce', '<?php echo wp_create_nonce('admin_operators_nonce'); ?>');
            formData.append('operatorId', id);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    loadOperators();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri brisanju operatera', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // USERS DATA FUNCTIONS
        
        // Load users from users_data table
        function loadUsers() {
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_users&nonce=<?php echo wp_create_nonce('admin_users_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    usersData = data.data;
                    originalUsers = [...data.data]; // Store original data for search
                    filteredUsers = [...data.data];
                    console.log('Users loaded successfully:', usersData);
                    // Apply default sorting (newest users last - ascending by id)
                    sortUsers('id');
                } else {
                    showAlert('Greška pri učitavanju korisnika: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri učitavanju korisnika', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Display users in table
        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">Nema korisnika u bazi</td></tr>';
                return;
            }
            
            tbody.innerHTML = users.map(user => {
                const ulogaText = getUserRoleText(user.ULOGA_ID);
                
                return `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.USER_ID || '-'}</td>
                        <td>${user.Ime || '-'}</td>
                        <td>${user.Prezime || '-'}</td>
                        <td>${user.EMAIL || '-'}</td>
                        <td>${user.MOBITEL || '-'}</td>
                        <td>${user.GRAD || '-'}</td>
                        <td>${ulogaText}</td>
                        <td>${user.SEZONA || '-'}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success-custom" onclick="editUser(${user.id})" title="Izmeni korisnika">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger-custom" onclick="deleteUser(${user.id})" title="Obriši korisnika">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function getUserRoleText(ulogaId) {
            switch(ulogaId) {
                case 1: return 'Sudija';
                case 2: return 'Delegat';
                case 3: return 'Instruktor';
                case 4: return 'Ostalo';
                default: return 'N/A';
            }
        }
        
        // Open add user modal
        function openAddUserModal() {
            document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Dodaj korisnika';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userUsername').value = '';
            document.getElementById('userPassword').required = true;
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
        
        // Edit user
        function editUser(id) {
            const user = usersData.find(u => u.id == id);
            if (!user) return;
            
            document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Izmeni korisnika';
            document.getElementById('userId').value = user.id;
            document.getElementById('userUserId').value = user.USER_ID || '';
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('userUsername').value = user.Username || '';
            document.getElementById('userIme').value = user.Ime || '';
            document.getElementById('userPrezime').value = user.Prezime || '';
            document.getElementById('userEmail').value = user.EMAIL || '';
            document.getElementById('userMobitel').value = user.MOBITEL || '';
            document.getElementById('userAdresa').value = user.ADRESA || '';
            document.getElementById('userGrad').value = user.GRAD || '';
            document.getElementById('userBanka').value = user.BANKA || '';
            document.getElementById('userRacun').value = user.RACUN || '';
            document.getElementById('userPozivNaBr').value = user.POZIV_NA_BR || '';
            document.getElementById('userZbor').value = user.ZBOR || '';
            document.getElementById('userKantonalniSavez').value = user.KANTONALNI_SAVEZ || '';
            document.getElementById('userUlogaId').value = user.ULOGA_ID || '';
            document.getElementById('userIznosClanarine').value = user.IZNOS_CLANARINE || '0.00';
            document.getElementById('userSezona').value = user.SEZONA || '2025';
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
        
        // Save user (add or edit)
        function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            const userId = document.getElementById('userId').value;
            const action = userId ? 'update_user' : 'add_user';
            
            formData.append('action', action);
            formData.append('nonce', '<?php echo wp_create_nonce('admin_users_nonce'); ?>');
            
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                    loadUsers();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri čuvanju korisnika', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Delete user
        function deleteUser(id) {
            const user = usersData.find(u => u.id == id);
            if (!user) return;
            
            if (!confirm(`Da li ste sigurni da želite da obrišete korisnika "${user.Ime} ${user.Prezime}"?`)) {
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('nonce', '<?php echo wp_create_nonce('admin_users_nonce'); ?>');
            formData.append('userId', id);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    loadUsers();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri brisanju korisnika', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // MATCHES FUNCTIONS
        
        // Load matches from user_match table
        function loadMatches() {
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_matches&nonce=<?php echo wp_create_nonce('admin_matches_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Podaci učitani uspešno:', data.data.length + ' utakmica');
                    allMatches = data.data; // Use allMatches for global access
                    matchesData = data.data; // Keep for backward compatibility
                    
                    // Reset pagination and sorting to defaults
                    matchesPagination.currentPage = 1;
                    
                    // Apply default sorting (newest matches first)
                    matchesSorting.column = 'id';
                    matchesSorting.direction = 'desc';
                    
                    // Apply filters and sorting (this will also update pagination)
                    applyMatchFilters();
                    populateMatchFilters(); // Populate filter dropdowns
                    
                    // Set the records per page selector to default value
                    const recordsPerPageSelect = document.getElementById('recordsPerPage');
                    if (recordsPerPageSelect) {
                        recordsPerPageSelect.value = matchesPagination.recordsPerPage.toString();
                    }
                } else {
                    console.error('Greška pri učitavanju:', data.data);
                    showAlert('Greška pri učitavanju utakmica: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri učitavanju utakmica', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Display matches in table
        function displayMatches(matches) {
            const tbody = document.getElementById('matchesTableBody');
            
            // Update pagination info
            matchesPagination.totalRecords = matches.length;
            
            if (matchesPagination.recordsPerPage === 'all') {
                matchesPagination.totalPages = 1;
                matchesPagination.currentPage = 1;
            } else {
                matchesPagination.totalPages = Math.ceil(matches.length / matchesPagination.recordsPerPage);
                if (matchesPagination.currentPage > matchesPagination.totalPages) {
                    matchesPagination.currentPage = 1;
                }
            }
            
            // Get paginated data
            let paginatedMatches;
            if (matchesPagination.recordsPerPage === 'all') {
                paginatedMatches = matches;
            } else {
                const startIndex = (matchesPagination.currentPage - 1) * matchesPagination.recordsPerPage;
                const endIndex = startIndex + matchesPagination.recordsPerPage;
                paginatedMatches = matches.slice(startIndex, endIndex);
            }
            
            if (paginatedMatches.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center">Nema utakmica u bazi</td></tr>';
            } else {
                tbody.innerHTML = paginatedMatches.map(match => {
                    // Debug - log match data to console
                    console.log('Match data:', match);
                    
                    return `
                        <tr>
                            <td>${match.id}</td>
                            <td>${match.id_utakmice || '-'}</td>
                            <td>${match.user_full_name || '-'}</td>
                            <td title="${match.utakmica || '-'}">${(match.utakmica || '-').substring(0, 50)}${(match.utakmica || '').length > 50 ? '...' : ''}</td>
                            <td>${match.liga || '-'}</td>
                            <td>${match.kolo || '-'}</td>
                            <td>${match.sezona || '-'}</td>
                            <td><span class="badge ${match.uloga === 'Sudija' ? 'bg-primary' : 'bg-success'}">${match.uloga || '-'}</span></td>
                            <td>${match.tarifa ? parseFloat(match.tarifa).toFixed(2) : '0.00'}</td>
                            <td><span class="badge ${match.status == 1 ? 'bg-success' : 'bg-secondary'}">${match.status == 1 ? 'Aktivan' : 'Neaktivan'}</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success-custom" onclick="editMatch(${match.id})" title="Izmeni utakmicu">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger-custom" onclick="deleteMatch(${match.id})" title="Obriši utakmicu">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
            
            // Update pagination controls
            updatePaginationControls(allMatches);
        }
        
        // Pagination functions
        function updatePaginationControls(allMatches) {
            const paginationInfo = document.getElementById('paginationInfo');
            const paginationControls = document.getElementById('paginationControls');
            const bottomPaginationInfo = document.getElementById('bottomPaginationInfo');
            const bottomPaginationControls = document.getElementById('bottomPaginationControls');
            
            // Update pagination info
            let infoText;
            if (matchesPagination.recordsPerPage === 'all') {
                infoText = `Prikazano: ${allMatches.length} od ${allMatches.length} zapisa`;
            } else {
                const startRecord = ((matchesPagination.currentPage - 1) * matchesPagination.recordsPerPage) + 1;
                const endRecord = Math.min(matchesPagination.currentPage * matchesPagination.recordsPerPage, allMatches.length);
                infoText = `Prikazano: ${startRecord}-${endRecord} od ${allMatches.length} zapisa (Stranica ${matchesPagination.currentPage} od ${matchesPagination.totalPages})`;
            }
            
            paginationInfo.textContent = infoText;
            bottomPaginationInfo.textContent = infoText;
            
            // Update pagination controls
            let controlsHTML = '';
            if (matchesPagination.recordsPerPage !== 'all' && matchesPagination.totalPages > 1) {
                controlsHTML = `
                    <nav aria-label="Paginacija">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item ${matchesPagination.currentPage === 1 ? 'disabled' : ''}">
                                <button class="page-link" onclick="goToPage(1)" ${matchesPagination.currentPage === 1 ? 'disabled' : ''}>
                                    <i class="fas fa-angle-double-left"></i>
                                </button>
                            </li>
                            <li class="page-item ${matchesPagination.currentPage === 1 ? 'disabled' : ''}">
                                <button class="page-link" onclick="goToPage(${matchesPagination.currentPage - 1})" ${matchesPagination.currentPage === 1 ? 'disabled' : ''}>
                                    <i class="fas fa-angle-left"></i>
                                </button>
                            </li>
                `;
                
                // Show page numbers (max 5 visible)
                const startPage = Math.max(1, matchesPagination.currentPage - 2);
                const endPage = Math.min(matchesPagination.totalPages, startPage + 4);
                
                for (let i = startPage; i <= endPage; i++) {
                    controlsHTML += `
                        <li class="page-item ${i === matchesPagination.currentPage ? 'active' : ''}">
                            <button class="page-link" onclick="goToPage(${i})">${i}</button>
                        </li>
                    `;
                }
                
                controlsHTML += `
                            <li class="page-item ${matchesPagination.currentPage === matchesPagination.totalPages ? 'disabled' : ''}">
                                <button class="page-link" onclick="goToPage(${matchesPagination.currentPage + 1})" ${matchesPagination.currentPage === matchesPagination.totalPages ? 'disabled' : ''}>
                                    <i class="fas fa-angle-right"></i>
                                </button>
                            </li>
                            <li class="page-item ${matchesPagination.currentPage === matchesPagination.totalPages ? 'disabled' : ''}">
                                <button class="page-link" onclick="goToPage(${matchesPagination.totalPages})" ${matchesPagination.currentPage === matchesPagination.totalPages ? 'disabled' : ''}>
                                    <i class="fas fa-angle-double-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                `;
            }
            
            paginationControls.innerHTML = controlsHTML;
            bottomPaginationControls.innerHTML = controlsHTML;
        }
        
        function changeRecordsPerPage() {
            const selectElement = document.getElementById('recordsPerPage');
            const newValue = selectElement.value;
            
            if (newValue === 'all') {
                matchesPagination.recordsPerPage = 'all';
            } else {
                matchesPagination.recordsPerPage = parseInt(newValue);
            }
            
            matchesPagination.currentPage = 1; // Reset to first page
            
            // Re-apply current filters and sorting
            applyMatchFilters();
        }
        
        function goToPage(pageNumber) {
            if (pageNumber >= 1 && pageNumber <= matchesPagination.totalPages) {
                matchesPagination.currentPage = pageNumber;
                // Re-apply current filters and sorting
                applyMatchFilters();
            }
        }
        
        // Populate filter dropdowns
        function populateMatchFilters() {
            if (!allMatches || allMatches.length === 0) return;
            
            // Note: User filter is now a text input, not a dropdown
            
            // Populate liga filter
            const ligaFilter = document.getElementById('filterMatchLiga');
            const uniqueLige = [...new Set(allMatches.map(m => m.liga).filter(l => l))];
            ligaFilter.innerHTML = '<option value="">Sve lige</option>' + 
                uniqueLige.map(liga => `<option value="${liga}">${liga}</option>`).join('');
            
            // Populate kolo filter
            const koloFilter = document.getElementById('filterMatchKolo');
            const uniqueKola = [...new Set(allMatches.map(m => m.kolo).filter(k => k))];
            koloFilter.innerHTML = '<option value="">Sva kola</option>' + 
                uniqueKola.map(kolo => `<option value="${kolo}">${kolo}</option>`).join('');
            
            // Populate sezona filter
            const sezonaFilter = document.getElementById('filterMatchSezona');
            const uniqueSezone = [...new Set(allMatches.map(m => m.sezona).filter(s => s))];
            sezonaFilter.innerHTML = '<option value="">Sve sezone</option>' + 
                uniqueSezone.map(sezona => `<option value="${sezona}">${sezona}</option>`).join('');
        }
        
        // Apply filters
        function applyMatchFilters() {
            console.log('Primenjuju se filteri, allMatches.length:', allMatches ? allMatches.length : 'undefined');
            
            const filterId = document.getElementById('filterMatchId').value;
            const filterUser = document.getElementById('filterMatchUser').value.toLowerCase();
            const filterUtakmica = document.getElementById('filterMatchUtakmica').value.toLowerCase();
            const filterLiga = document.getElementById('filterMatchLiga').value;
            const filterKolo = document.getElementById('filterMatchKolo').value;
            const filterSezona = document.getElementById('filterMatchSezona').value;
            const filterUloga = document.getElementById('filterMatchUloga').value;
            const filterStatus = document.getElementById('filterMatchStatus').value;
            
            let filteredMatches = allMatches.filter(match => {
                // ID Utakmice filter (searching the actual match ID, not table row ID)
                if (filterId && match.id_utakmice != filterId) return false;
                
                // User filter (ime/prezime search - contains)
                if (filterUser && !match.user_full_name.toLowerCase().includes(filterUser)) return false;
                
                // Utakmica filter (contains search)
                if (filterUtakmica && !match.utakmica.toLowerCase().includes(filterUtakmica)) return false;
                
                // Liga filter
                if (filterLiga && match.liga !== filterLiga) return false;
                
                // Kolo filter
                if (filterKolo && match.kolo !== filterKolo) return false;
                
                // Sezona filter
                if (filterSezona && match.sezona !== filterSezona) return false;
                
                // Uloga filter
                if (filterUloga && match.uloga !== filterUloga) return false;
                
                // Status filter
                if (filterStatus !== '' && match.status != filterStatus) return false;
                
                return true;
            });
            
            // Apply current sorting to filtered results
            const sortedFilteredMatches = [...filteredMatches].sort((a, b) => {
                let aVal = a[matchesSorting.column] || '';
                let bVal = b[matchesSorting.column] || '';
                
                // Handle numeric columns
                if (matchesSorting.column === 'id' || matchesSorting.column === 'id_utakmice' || matchesSorting.column === 'tarifa' || matchesSorting.column === 'status') {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else {
                    // String comparison (case insensitive)
                    aVal = aVal.toString().toLowerCase();
                    bVal = bVal.toString().toLowerCase();
                }
                
                if (matchesSorting.direction === 'asc') {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                } else {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                }
            });
            
            displayMatches(sortedFilteredMatches);
            
            // Show filter results
            const resultsDiv = document.getElementById('filterResults');
            const resultsText = document.getElementById('filterResultsText');
            
            if (filteredMatches.length === matchesData.length) {
                resultsDiv.classList.add('d-none');
            } else {
                resultsDiv.classList.remove('d-none');
                resultsText.textContent = `Prikazano ${filteredMatches.length} od ${matchesData.length} utakmica`;
                
                // Change color based on results
                resultsDiv.className = 'alert d-block';
                if (filteredMatches.length === 0) {
                    resultsDiv.classList.add('alert-warning');
                    resultsText.textContent = 'Nema utakmica koje odgovaraju filterima';
                } else {
                    resultsDiv.classList.add('alert-success');
                }
            }
        }
        
        // Clear all filters
        function clearMatchFilters() {
            document.getElementById('filterMatchId').value = '';
            document.getElementById('filterMatchUser').value = '';
            document.getElementById('filterMatchUtakmica').value = '';
            document.getElementById('filterMatchLiga').value = '';
            document.getElementById('filterMatchKolo').value = '';
            document.getElementById('filterMatchSezona').value = '';
            document.getElementById('filterMatchUloga').value = '';
            document.getElementById('filterMatchStatus').value = '';
            
            displayMatches(matchesData);
            
            // Hide filter results
            const resultsDiv = document.getElementById('filterResults');
            resultsDiv.classList.add('d-none');
        }
        
        // Populate users dropdown
        function populateUsersDropdown() {
            const dropdown = document.getElementById('matchClan');
            
            console.log('populateUsersDropdown called');
            console.log('usersData:', usersData);
            console.log('dropdown element:', dropdown);
            
            // Clear existing options except the first one
            dropdown.innerHTML = '<option value="">Izaberite korisnika...</option>';
            
            if (usersData && usersData.length > 0) {
                usersData.forEach(user => {
                    console.log('Processing user:', user);
                    const fullName = (user.Ime && user.Prezime) ? `${user.Ime} ${user.Prezime}` : user.Username;
                    const option = document.createElement('option');
                    // Use USER_ID as the correct field for joining with user_match.user_id
                    const userId = user.USER_ID;
                    option.value = userId;
                    option.textContent = `${fullName} (ID: ${userId})`;
                    dropdown.appendChild(option);
                    console.log('Added user option:', option.value, option.textContent);
                });
            } else {
                console.log('No usersData available or empty');
            }
        }
        
        // Open add match modal
        function openAddMatchModal() {
            document.getElementById('matchModalTitle').innerHTML = '<i class="fas fa-futbol"></i> Dodaj utakmicu';
            document.getElementById('matchForm').reset();
            document.getElementById('matchId').value = '';
            
            // Populate users dropdown
            populateUsersDropdown();
            
            const modal = new bootstrap.Modal(document.getElementById('matchModal'));
            modal.show();
        }
        
        // Edit match
        function editMatch(id) {
            const match = matchesData.find(m => m.id == id);
            if (!match) return;
            
            document.getElementById('matchModalTitle').innerHTML = '<i class="fas fa-futbol"></i> Izmeni utakmicu';
            
            // Populate users dropdown first
            populateUsersDropdown();
            
            document.getElementById('matchId').value = match.id;
            document.getElementById('matchClan').value = match.user_id || match.clan || '';
            document.getElementById('matchIdUtakmice').value = match.id_utakmice || '';
            document.getElementById('matchUtakmica').value = match.utakmica || '';
            document.getElementById('matchLiga').value = match.liga || '';
            document.getElementById('matchKolo').value = match.kolo || '';
            document.getElementById('matchSezona').value = match.sezona || '';
            document.getElementById('matchTarifa').value = match.tarifa || '0.00';
            document.getElementById('matchOcjena').value = match.ocjena || '0';
            document.getElementById('matchStatus').value = match.status || '0';
            document.getElementById('matchUloga').value = match.uloga || '';
            document.getElementById('matchDatumObavjesti').value = match.datum_obavjesti || '';
            document.getElementById('matchStatusSsk').value = match.status_ssk || '0';
            document.getElementById('matchSuspendovan').value = match.suspendovan || '0';
            document.getElementById('matchKomisija').value = match.komisija || '';
            document.getElementById('matchKomentar').value = match.komentar || '';
            
            const modal = new bootstrap.Modal(document.getElementById('matchModal'));
            modal.show();
        }
        
        // Save match (add or edit)
        function saveMatch() {
            const form = document.getElementById('matchForm');
            const formData = new FormData(form);
            
            const matchId = document.getElementById('matchId').value;
            const action = matchId ? 'update_match' : 'add_match';
            
            formData.append('action', action);
            formData.append('nonce', '<?php echo wp_create_nonce('admin_matches_nonce'); ?>');
            
            showLoading(true);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('matchModal')).hide();
                    loadMatches();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri čuvanju utakmice', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Delete match
        function deleteMatch(id) {
            const match = matchesData.find(m => m.id == id);
            if (!match) return;
            
            if (!confirm(`Da li ste sigurni da želite da obrišete utakmicu "${match.utakmica}"?`)) {
                return;
            }
            
            showLoading(true);
            
            const formData = new FormData();
            formData.append('action', 'delete_match');
            formData.append('nonce', '<?php echo wp_create_nonce('admin_matches_nonce'); ?>');
            formData.append('matchId', id);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.data.message, 'success');
                    loadMatches();
                    loadStatistics();
                } else {
                    showAlert('Greška: ' + data.data, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Greška pri brisanju utakmice', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Sorting functionality
        let usersSort = { column: 'id', direction: 'asc' }; // Default: newest users last
        function sortMatches(column) {
            // Toggle direction if same column, otherwise default to asc
            if (matchesSorting.column === column) {
                matchesSorting.direction = matchesSorting.direction === 'asc' ? 'desc' : 'asc';
            } else {
                matchesSorting.column = column;
                matchesSorting.direction = 'asc';
            }
            
            // Update sort icons
            updateSortIcons('matches', column, matchesSorting.direction);
            
            // Sort the data
            const filteredData = getFilteredMatches();
            const sortedData = [...filteredData].sort((a, b) => {
                let aVal = a[column] || '';
                let bVal = b[column] || '';
                
                // Handle numeric columns
                if (column === 'id' || column === 'id_utakmice' || column === 'tarifa' || column === 'status') {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else {
                    // String comparison (case insensitive)
                    aVal = aVal.toString().toLowerCase();
                    bVal = bVal.toString().toLowerCase();
                }
                
                if (matchesSorting.direction === 'asc') {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                } else {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                }
            });
            
            displayMatches(sortedData);
        }
        
        function sortUsers(column) {
            // Toggle direction if same column, otherwise default to asc
            if (usersSort.column === column) {
                usersSort.direction = usersSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                usersSort.column = column;
                usersSort.direction = 'asc';
            }
            
            // Update sort icons
            updateSortIcons('users', column, usersSort.direction);
            
            // Sort the filtered data (use filteredUsers if available, otherwise usersData)
            const dataToSort = filteredUsers.length > 0 ? filteredUsers : usersData;
            const sortedData = [...dataToSort].sort((a, b) => {
                let aVal = a[column] || '';
                let bVal = b[column] || '';
                
                // Handle numeric columns
                if (column === 'id' || column === 'USER_ID' || column === 'ULOGA_ID') {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else {
                    // String comparison (case insensitive)
                    aVal = aVal.toString().toLowerCase();
                    bVal = bVal.toString().toLowerCase();
                }
                
                if (usersSort.direction === 'asc') {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                } else {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                }
            });

            // Update the appropriate data array
            if (filteredUsers.length > 0) {
                filteredUsers = sortedData;
            } else {
                usersData = sortedData;
            }

            displayUsers(sortedData);
        }
        
        function updateSortIcons(table, activeColumn, direction) {
            // Remove all sort classes and reset icons
            const prefix = table === 'matches' ? 'sort-' : 'sort-user-';
            document.querySelectorAll(`[id^="${prefix}"]`).forEach(icon => {
                icon.className = 'fas fa-sort';
                icon.parentElement.classList.remove('sorted-asc', 'sorted-desc');
            });
            
            // Update active column icon
            const activeIcon = document.getElementById(`${prefix}${activeColumn}`);
            if (activeIcon) {
                activeIcon.className = direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                activeIcon.parentElement.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
            }
        }
        
        function getFilteredMatches() {
            // Get currently filtered matches (respects active filters)
            const filterId = document.getElementById('filterMatchId').value;
            const filterUser = document.getElementById('filterMatchUser').value.toLowerCase();
            const filterUtakmica = document.getElementById('filterMatchUtakmica').value.toLowerCase();
            const filterLiga = document.getElementById('filterMatchLiga').value;
            const filterKolo = document.getElementById('filterMatchKolo').value;
            const filterSezona = document.getElementById('filterMatchSezona').value;
            const filterUloga = document.getElementById('filterMatchUloga').value;
            const filterStatus = document.getElementById('filterMatchStatus').value;
            
            if (!filterId && !filterUser && !filterUtakmica && !filterLiga && !filterKolo && !filterSezona && !filterUloga && filterStatus === '') {
                return allMatches;
            }
            
            return allMatches.filter(match => {
                if (filterId && match.id_utakmice != filterId) return false;
                if (filterUser && !match.user_full_name.toLowerCase().includes(filterUser)) return false;
                if (filterUtakmica && !match.utakmica.toLowerCase().includes(filterUtakmica)) return false;
                if (filterLiga && match.liga !== filterLiga) return false;
                if (filterKolo && match.kolo !== filterKolo) return false;
                if (filterSezona && match.sezona !== filterSezona) return false;
                if (filterUloga && match.uloga !== filterUloga) return false;
                if (filterStatus !== '' && match.status != filterStatus) return false;
                return true;
            });
        }
        
        // Utility functions
        function showLoading(show) {
            document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
        }
        
        function showAlert(message, type) {
            // Create and show alert notification
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // SEARCH FUNCTIONALITY FOR OPERATORS
        let originalOperators = []; // Store original data
        let filteredOperators = [];

        function searchOperators() {
            const searchTerm = document.getElementById('operatorsSearch').value.toLowerCase().trim();

            if (searchTerm === '') {
                // If empty search, show all operators
                filteredOperators = [...originalOperators];
                displayOperators(filteredOperators);
                hideSearchResults('operators');
                return;
            }

            // Filter operators based on search term (search across all fields)
            filteredOperators = originalOperators.filter(operator => {
                return (
                    (operator.id && operator.id.toString().toLowerCase().includes(searchTerm)) ||
                    (operator.Username && operator.Username.toLowerCase().includes(searchTerm)) ||
                    (operator.ime_prezime && operator.ime_prezime.toLowerCase().includes(searchTerm)) ||
                    (operator.Email && operator.Email.toLowerCase().includes(searchTerm)) ||
                    (operator.uloga_naziv && operator.uloga_naziv.toLowerCase().includes(searchTerm)) ||
                    (operator.tip_komisije && operator.tip_komisije.toLowerCase().includes(searchTerm)) ||
                    (operator.osnovne_privilegije && operator.osnovne_privilegije.toLowerCase().includes(searchTerm)) ||
                    (operator.poljne_privilegije && operator.poljne_privilegije.toLowerCase().includes(searchTerm)) ||
                    (operator.status_naziv && operator.status_naziv.toLowerCase().includes(searchTerm))
                );
            });

            displayOperators(filteredOperators);
            showSearchResults('operators', filteredOperators.length, originalOperators.length, searchTerm);
        }

        function clearOperatorsSearch() {
            document.getElementById('operatorsSearch').value = '';
            filteredOperators = [...originalOperators];
            displayOperators(filteredOperators);
            hideSearchResults('operators');
        }

        // SEARCH FUNCTIONALITY FOR USERS
        let originalUsers = []; // Store original data
        let filteredUsers = [];

        function searchUsers() {
            const searchTerm = document.getElementById('usersSearch').value.toLowerCase().trim();

            if (searchTerm === '') {
                // If empty search, show all users
                filteredUsers = [...originalUsers];
                displayUsers(filteredUsers);
                hideSearchResults('users');
                return;
            }

            // Filter users based on search term (search across all fields)
            filteredUsers = originalUsers.filter(user => {
                return (
                    (user.id && user.id.toString().toLowerCase().includes(searchTerm)) ||
                    (user.USER_ID && user.USER_ID.toString().toLowerCase().includes(searchTerm)) ||
                    (user.Ime && user.Ime.toLowerCase().includes(searchTerm)) ||
                    (user.Prezime && user.Prezime.toLowerCase().includes(searchTerm)) ||
                    (user.EMAIL && user.EMAIL.toLowerCase().includes(searchTerm)) ||
                    (user.MOBITEL && user.MOBITEL.toLowerCase().includes(searchTerm)) ||
                    (user.GRAD && user.GRAD.toLowerCase().includes(searchTerm)) ||
                    (user.ULOGA_naziv && user.ULOGA_naziv.toLowerCase().includes(searchTerm)) ||
                    (user.SEZONA && user.SEZONA.toString().toLowerCase().includes(searchTerm))
                );
            });

            displayUsers(filteredUsers);
            showSearchResults('users', filteredUsers.length, originalUsers.length, searchTerm);
        }

        function clearUsersSearch() {
            document.getElementById('usersSearch').value = '';
            filteredUsers = [...originalUsers];
            displayUsers(filteredUsers);
            hideSearchResults('users');
        }

        // Helper functions for search results display
        function showSearchResults(type, filteredCount, totalCount, searchTerm) {
            const resultsDiv = document.getElementById(type + 'SearchResults');
            const resultsText = document.getElementById(type + 'SearchResultsText');

            resultsText.innerHTML = `Pronađeno ${filteredCount} od ${totalCount} ${getEntityName(type)} za pretragu: "<strong>${searchTerm}</strong>"`;
            resultsDiv.classList.remove('d-none');
        }

        function hideSearchResults(type) {
            const resultsDiv = document.getElementById(type + 'SearchResults');
            resultsDiv.classList.add('d-none');
        }

        function getEntityName(type) {
            switch(type) {
                case 'operators': return 'operatera';
                case 'users': return 'korisnika';
                default: return 'zapisa';
            }
        }

        // Add Enter key support for search inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Operators search on Enter key
            document.getElementById('operatorsSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchOperators();
                }
            });

            // Users search on Enter key
            document.getElementById('usersSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchUsers();
                }
            });
        });
    </script>
</body>
</html>
