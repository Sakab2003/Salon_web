@props(["extra"=>""])

<div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100 position-relative overflow-hidden">
    <!-- Animated Background Gradients -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <div class="container position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card glass-card border-0 shadow-lg overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            {{ $logo }}
                        </div>

                        <div>
                            {{ $slot }}
                        </div>

                        <div class="text-center mt-4 {{ $extra ? '' : 'd-none' }}">
                            {{ $extra }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Light Glassmorphic Login Styles */
.auth-wrapper {
    background: #f8fafc; /* Light modern base */
    color: #334155;
    min-height: 100vh;
}

/* Floating animated shapes */
.bg-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.6;
    animation: float 12s infinite ease-in-out alternate;
    z-index: 0;
}

.bg-shape-1 {
    width: 500px;
    height: 500px;
    background: #818cf8; /* Soft Indigo */
    top: -100px;
    left: -150px;
    animation-delay: 0s;
}

.bg-shape-2 {
    width: 600px;
    height: 600px;
    background: #e879f9; /* Soft Fuchsia */
    bottom: -200px;
    right: -150px;
    animation-delay: 3s;
}

.bg-shape-3 {
    width: 400px;
    height: 400px;
    background: #38bdf8; /* Soft Sky blue */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 6s;
    opacity: 0.4;
}

@keyframes float {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(40px, -60px) scale(1.1); }
}

/* Glass Card */
.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.glass-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

/* Text overrides for readability in light mode */
.glass-card .card-body {
    color: #334155;
}
.glass-card h1, .glass-card h2, .glass-card h3, .glass-card h4, .glass-card h5, .glass-card h6 {
    color: #0f172a;
    font-weight: 700;
}
.glass-card .text-gray-600 {
    color: #64748b !important;
}
.glass-card a {
    color: #4f46e5 !important;
    text-decoration: none;
    transition: color 0.3s ease;
}
.glass-card a:hover {
    color: #3730a3 !important;
}

/* Custom form inputs for glass theme */
.glass-card .form-control, .glass-card .form-check-input {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    color: #334155;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}
.glass-card .form-control:focus {
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    color: #0f172a;
}
.glass-card .form-label, .glass-card label {
    color: #475569;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.glass-card .btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-size: 0.875rem;
    color: #ffffff;
    transition: all 0.3s ease;
    box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
}
.glass-card .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 20px -5px rgba(79, 70, 229, 0.4);
    background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
}
</style>
