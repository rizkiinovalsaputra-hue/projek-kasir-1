<!DOCTYPE html>
<html>
<head>
    <title>Kasir pro  - Sistem Kasir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        
        /* Animated Background */
        .bg-animated {
            position: fixed;
            width: 100%;
            height: 100%;
            background: linear-gradient(-45deg, #cbcfd3ff, #802e2eff, #0eb2f3ff, #e41e50ff);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            z-index: -1;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Header */
        .header {
            background: rgba(255,255,255,0.95);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .auth-buttons { display: flex; gap: 15px; }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-login {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-login:hover {
            background: #22abeaff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 65, 88, 0.4);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        /* Hero Section */
        .hero {
            text-align: center;
            padding: 120px 20px;
            color: white;
            position: relative;
        }
        .hero h1 {
            font-size: 64px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 20px rgba(0,0,0,0.2);
            animation: fadeInUp 1s ease;
        }
        .hero p {
            font-size: 24px;
            margin-bottom: 40px;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.2s both;
        }
        .hero .btn {
            font-size: 18px;
            padding: 18px 45px;
            animation: fadeInUp 1s ease 0.4s both;
            background: white;
            color: #667eea;
            font-weight: bold;
        }
        .hero .btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255,255,255,0.3);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Features Section */
        .features {
            padding: 100px 20px;
            background: white;
            position: relative;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .features h2 {
            text-align: center;
            margin-bottom: 60px;
            font-size: 42px;
            background: linear-gradient(135deg, #0d84d3ff 0%, #26bdefff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
        }
        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.2);
        }
        .feature-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .feature-card p {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
        }
        
        /* Stats Section */
        .stats {
            padding: 80px 20px;
            background: linear-gradient(135deg, #0f31ccff 0%, #851af1ff 100%);
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        .stat-item h3 {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .stat-item p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
    </style>
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="header">
        <div class="logo"> Kasir </div>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register">Daftar </a>
        </div>
    </div>
    
    <div class="hero">
        <h1>Sistem Kasir</h1>
        <p>Kelola bisnis Anda dengan mudah dan efisien</p>
        <a href="login.php" class="btn">gaskeun →</a>
    </div>
    
    <div class="features">
        <div class="container">
            <h2> Fitur Unggulan</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Transaksi Cepat</h3>
                    <p>Proses transaksi dalam hitungan detik dengan interface yang intuitif dan mudah digunakan</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Laporan Real-time</h3>
                    <p>Monitor penjualan dan stok secara real-time untuk pengambilan keputusan yang lebih baik</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Multi User</h3>
                    <p>Sistem approval transaksi dengan role admin dan user untuk keamanan maksimal</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Responsive Design</h3>
                    <p>Akses dari perangkat apapun dengan tampilan yang optimal di semua layar</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Keamanan Terjamin</h3>
                    <p>Data Anda aman dengan enkripsi dan sistem autentikasi yang kuat</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                    <h3>Manajemen Stok</h3>
                    <p>Kelola inventori dengan mudah dan dapatkan notifikasi stok menipis</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>100+</h3>
                <p>Transaksi Harian</p>
            </div>
            <div class="stat-item">
                <h3>99.9%</h3>
                <p>Uptime</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>Support</p>
            </div>
            <div class="stat-item">
                <h3>⚡</h3>
                <p>Super Cepat</p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy;Sistem Kasir Modern untuk Bisnis Anda.</p>
    </div>
</body>
</html>