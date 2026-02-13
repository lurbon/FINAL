<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance en cours - Système temporairement indisponible</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=IBM+Plex+Mono:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0a0e17;
            --secondary-dark: #161b2e;
            --accent-orange: #ff6b35;
            --accent-amber: #ffa62b;
            --accent-blue: #00d9ff;
            --text-light: #e8eaed;
            --text-dim: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Mono', monospace;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 107, 53, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 107, 53, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridPulse 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes gridPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--accent-orange);
            border-radius: 50%;
            opacity: 0;
            animation: float 8s infinite;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 30%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 50%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 70%; animation-delay: 1s; }
        .particle:nth-child(5) { left: 90%; animation-delay: 3s; }

        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) scale(1);
                opacity: 0;
            }
        }

        .container {
            max-width: 900px;
            width: 90%;
            text-align: center;
            z-index: 10;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-icon {
            width: 300px;
            height: 300px;
            margin: 0 auto 40px;
            position: relative;
        }

        .maintenance-icon svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 40px rgba(255, 107, 53, 0.3));
        }

        @keyframes rotateGear {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .gear-1 {
            animation: rotateGear 8s linear infinite;
            transform-origin: center;
        }

        .gear-2 {
            animation: rotateGear 6s linear infinite reverse;
            transform-origin: center;
        }

        .status-badge {
            display: inline-block;
            background: rgba(255, 107, 53, 0.15);
            border: 2px solid var(--accent-orange);
            padding: 12px 24px;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 30px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(255, 107, 53, 0);
            }
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-amber) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            animation: titleGlow 3s ease-in-out infinite;
        }

        @keyframes titleGlow {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(255, 107, 53, 0.5));
            }
            50% {
                filter: drop-shadow(0 0 40px rgba(255, 107, 53, 0.8));
            }
        }

        .subtitle {
            font-size: clamp(1rem, 3vw, 1.3rem);
            color: var(--text-dim);
            margin-bottom: 40px;
            font-weight: 300;
            line-height: 1.6;
        }

        .info-box {
            background: rgba(22, 27, 46, 0.6);
            border: 1px solid rgba(255, 107, 53, 0.2);
            border-radius: 16px;
            padding: 40px;
            margin: 40px 0;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .info-box:hover {
            border-color: rgba(255, 107, 53, 0.5);
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(255, 107, 53, 0.2);
        }

        .info-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent-amber);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .info-text {
            color: var(--text-light);
            line-height: 1.8;
            font-size: 1rem;
        }

        .progress-container {
            margin-top: 30px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 107, 53, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-orange), var(--accent-amber), var(--accent-blue));
            background-size: 200% 100%;
            border-radius: 10px;
            animation: progressGlow 3s ease-in-out infinite, progressMove 2s linear infinite;
            width: 65%;
        }

        @keyframes progressGlow {
            0%, 100% {
                box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(255, 107, 53, 0.8);
            }
        }

        @keyframes progressMove {
            0% {
                background-position: 0% 0%;
            }
            100% {
                background-position: 100% 0%;
            }
        }

        .progress-text {
            margin-top: 15px;
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        .contact-info {
            margin-top: 50px;
            padding: 25px;
            background: rgba(10, 14, 23, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }

        .contact-info p {
            margin: 8px 0;
            color: var(--text-dim);
        }

        .contact-info a {
            color: var(--accent-blue);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .contact-info a:hover {
            color: var(--accent-amber);
            text-shadow: 0 0 10px rgba(255, 166, 43, 0.5);
        }

        @media (max-width: 768px) {
            .maintenance-icon {
                width: 200px;
                height: 200px;
            }

            .info-box {
                padding: 25px;
            }

            h1 {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="status-badge">
            ⚡ Système en maintenance
        </div>

        <div class="maintenance-icon">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <g class="gear-1">
                    <circle cx="100" cy="100" r="50" fill="none" stroke="#ff6b35" stroke-width="3"/>
                    <circle cx="100" cy="100" r="20" fill="#ffa62b"/>
                    <rect x="95" y="45" width="10" height="10" fill="#ff6b35"/>
                    <rect x="95" y="145" width="10" height="10" fill="#ff6b35"/>
                    <rect x="145" y="95" width="10" height="10" fill="#ff6b35"/>
                    <rect x="45" y="95" width="10" height="10" fill="#ff6b35"/>
                    <rect x="128" y="58" width="10" height="10" fill="#ff6b35" transform="rotate(45 133 63)"/>
                    <rect x="128" y="132" width="10" height="10" fill="#ff6b35" transform="rotate(45 133 137)"/>
                    <rect x="62" y="58" width="10" height="10" fill="#ff6b35" transform="rotate(-45 67 63)"/>
                    <rect x="62" y="132" width="10" height="10" fill="#ff6b35" transform="rotate(-45 67 137)"/>
                </g>
                
                <g class="gear-2">
                    <circle cx="155" cy="55" r="25" fill="none" stroke="#00d9ff" stroke-width="2"/>
                    <circle cx="155" cy="55" r="10" fill="#00d9ff"/>
                    <rect x="152" y="28" width="6" height="6" fill="#00d9ff"/>
                    <rect x="152" y="76" width="6" height="6" fill="#00d9ff"/>
                    <rect x="176" y="52" width="6" height="6" fill="#00d9ff"/>
                    <rect x="128" y="52" width="6" height="6" fill="#00d9ff"/>
                </g>
                
                <g>
                    <rect x="35" y="130" width="60" height="8" fill="#ffa62b" transform="rotate(-30 65 134)"/>
                    <circle cx="90" cy="155" r="8" fill="none" stroke="#ffa62b" stroke-width="3"/>
                    <rect x="20" y="126" width="20" height="16" fill="#ff6b35" transform="rotate(-30 30 134)"/>
                </g>
                
                <circle cx="40" cy="40" r="2" fill="#00d9ff">
                    <animate attributeName="opacity" values="0;1;0" dur="2s" repeatCount="indefinite"/>
                </circle>
                <circle cx="170" cy="150" r="2" fill="#ffa62b">
                    <animate attributeName="opacity" values="1;0;1" dur="2.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="180" cy="30" r="2" fill="#ff6b35">
                    <animate attributeName="opacity" values="0;1;0" dur="1.8s" repeatCount="indefinite"/>
                </circle>
            </svg>
        </div>

        <h1>Maintenance en cours</h1>
        <p class="subtitle">
            Notre système fait peau neuve pour vous offrir une meilleure expérience.<br>
            Nous serons bientôt de retour, plus performants que jamais.
        </p>
    </div>
</body>
</html>
