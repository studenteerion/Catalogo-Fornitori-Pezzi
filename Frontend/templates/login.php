<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Catalogo Fornitori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/global.css" rel="stylesheet">
    <link href="/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="back-button">
        <a href="/" title="Torna all'elenco delle query">← Indietro</a>
    </div>
    
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <h1>Catalogo Fornitori</h1>
            <p id="header-subtitle">Accedi al Catalogo</p>
        </div>
        
        <!-- Login Body -->
        <div class="login-body">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link active" 
                        id="login-tab" 
                        type="button" 
                        role="tab"
                        onclick="switchTab('login')"
                    >
                        Login
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link" 
                        id="register-tab" 
                        type="button" 
                        role="tab"
                        onclick="switchTab('register')"
                    >
                        Registrati
                    </button>
                </li>
            </ul>

            <!-- Login Form -->
            <div id="login-form-container" class="tab-pane active">
                <div class="alert alert-danger" id="login-error" role="alert"></div>
                <form id="login-form" onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="login-email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="login-email" 
                            class="form-control" 
                            placeholder="Inserisci la tua email"
                            required
                            autocomplete="email"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="login-password" 
                            class="form-control" 
                            placeholder="Inserisci la tua password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary-gradient">Accedi</button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register-form-container" class="tab-pane hidden">
                <div class="alert alert-danger" id="register-error" role="alert"></div>
                <div class="alert alert-success" id="register-success" role="alert"></div>
                <form id="register-form" onsubmit="handleRegister(event)">
                    <div class="form-group">
                        <label for="register-email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="register-email" 
                            class="form-control" 
                            placeholder="La tua email"
                            required
                            autocomplete="email"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="register-nome" class="form-label">Nome</label>
                        <input 
                            type="text" 
                            id="register-nome" 
                            class="form-control" 
                            placeholder="Nome azienda"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="register-indirizzo" class="form-label">Indirizzo</label>
                        <input 
                            type="text" 
                            id="register-indirizzo" 
                            class="form-control" 
                            placeholder="Indirizzo completo"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="register-password" 
                            class="form-control" 
                            placeholder="Scegli una password"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm" class="form-label">Conferma Password</label>
                        <input 
                            type="password" 
                            id="register-confirm" 
                            class="form-control" 
                            placeholder="Conferma la password"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary-gradient">Registrati</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/config.js"></script>
    <script src="/js/auth.js"></script>
</body>
</html>

