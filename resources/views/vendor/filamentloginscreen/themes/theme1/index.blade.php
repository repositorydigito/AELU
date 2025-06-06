@props([
    'heading' => null,
    'subheading' => null,
])


<!-- Script crítico para bloquear detección automática - DEBE ir antes que todo -->
<script>
    // Bloquear detección automática del sistema INMEDIATAMENTE
    (function() {
        // Forzar light theme en localStorage
        localStorage.setItem('theme', 'light');
        
        // Remover clase dark del documento
        document.documentElement.classList.remove('dark');
        
        // Sobrescribir window.matchMedia para bloquear prefers-color-scheme
        const originalMatchMedia = window.matchMedia;
        window.matchMedia = function(query) {
            if (query.includes('prefers-color-scheme')) {
                // Devolver un objeto que siempre reporte light
                return {
                    matches: false, // Nunca dark
                    addListener: function() {},
                    removeListener: function() {},
                    addEventListener: function() {},
                    removeEventListener: function() {},
                    dispatchEvent: function() { return true; }
                };
            }
            return originalMatchMedia.call(this, query);
        };
        
        // Interceptar cualquier intento de cambiar el theme
        const originalSetItem = Storage.prototype.setItem;
        Storage.prototype.setItem = function(key, value) {
            if (key === 'theme' && (value === 'dark' || value === 'system')) {
                console.log('Bloqueando cambio automático a:', value);
                return; // Bloquear cambios automáticos a dark o system
            }
            return originalSetItem.call(this, key, value);
        };
    })();
</script>

<div class="flex min-h-screen w-full bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Contenedor izquierdo (imagen) -->
    <div class="hidden lg:block lg:w-1/2 h-screen">
        <img
            src="{{ asset('images/imgLogin.jpeg') }}"
            alt="imgLogin"
            class="w-full h-full object-cover"
        >
    </div>
    <!-- Contenedor derecho (formulario) -->
    <div class="w-full lg:w-1/2 min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center transition-colors duration-200">

        <div class="max-w-md w-full p-6">

            <div class="flex items-center justify-center mb-8">
                <img src="{{ asset('images/logoAELU.svg') }}" alt="logoAELU"
                    style="margin-bottom:20px; width: 15rem; height: auto;"
                    class="transition-all duration-200 dark:brightness-0 dark:invert">
            </div>

            <section class="grid gap-y-6">
                <x-filament-panels::header.simple
                    :heading="$heading ??= $this->getHeading()"
                    :logo="!request()->routeIs('filament.admin.auth.login') && $this->hasLogo()"
                    :subheading="$subheading ??= $this->getSubHeading()"
                />

                @if (filament()->hasRegistration())
                    <x-slot name="subheading">
                        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                        {{ $this->registerAction }}
                    </x-slot>
                @endif

                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>
            </section>
        </div>
    </div>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Script adicional para mantener el control -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Forzar light theme constantemente
            const forceLight = () => {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                
                // Si Alpine está disponible, forzar en el store
                if (window.Alpine && window.Alpine.store) {
                    try {
                        window.Alpine.store('theme', 'light');
                    } catch (e) {
                        console.log('Alpine store no disponible');
                    }
                }
            };
            
            // Ejecutar inmediatamente
            forceLight();
            
            // Ejecutar cada 100ms durante los primeros 3 segundos
            let counter = 0;
            const interval = setInterval(() => {
                forceLight();
                counter++;
                if (counter > 30) { // 30 * 100ms = 3 segundos
                    clearInterval(interval);
                }
            }, 100);
            
            // Observer para detectar cambios en la clase dark del documento
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (document.documentElement.classList.contains('dark')) {
                            console.log('Detectado cambio a dark mode, revirtiendo...');
                            document.documentElement.classList.remove('dark');
                        }
                    }
                });
            });
            
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>

    <style>
    /* Transiciones suaves para cambios de tema */
    * {
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    /* === SOLO ESTILOS DARK === */
    /* Solo aplicamos estilos específicos para dark mode para mejorar la legibilidad */
    /* Los estilos light se manejan automáticamente por Filament y Tailwind */

    /* Fondo principal en dark */
    .dark .fi-simple-main {
        background-color: rgb(17 24 39) !important; /* dark:bg-gray-900 */
    }

    /* Labels de campos en dark */
    .dark .fi-fo-field-wrp-label {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    /* Inputs en dark */
    .dark .fi-input {
        background-color: rgb(55 65 81) !important; /* dark:bg-gray-700 */
        border-color: rgb(75 85 99) !important; /* dark:border-gray-600 */
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-input:focus {
        border-color: rgb(59 130 246) !important; /* dark:border-blue-500 */
        ring-color: rgb(59 130 246) !important; /* dark:ring-blue-500 */
    }

    .dark .fi-input::placeholder {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }

    /* Labels generales en dark */
    .dark label {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    /* Botones primarios en dark */
    .dark .fi-btn-color-primary {
        background-color: rgb(37 99 235) !important; /* dark:bg-blue-600 */
        color: rgb(255 255 255) !important; /* dark:text-white */
    }

    .dark .fi-btn-color-primary:hover {
        background-color: rgb(29 78 216) !important; /* dark:hover:bg-blue-700 */
    }

    /* Mensajes de error en dark */
    .dark .fi-fo-field-wrp-error-message {
        color: rgb(248 113 113) !important; /* dark:text-red-400 */
    }

    /* Mensajes de ayuda en dark */
    .dark .fi-fo-field-wrp-hint {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }

    /* Encabezados en dark */
    .dark h1, .dark h2, .dark h3, .dark .fi-header-heading {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-header-subheading {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }

    /* === ESTILOS ADICIONALES PARA DARK === */
    /* Checkbox y radio buttons en dark */
    .dark .fi-fo-checkbox input[type="checkbox"],
    .dark .fi-fo-radio input[type="radio"] {
        background-color: rgb(55 65 81) !important; /* dark:bg-gray-700 */
        border-color: rgb(75 85 99) !important; /* dark:border-gray-600 */
    }

    /* Select dropdown en dark */
    .dark .fi-select select {
        background-color: rgb(55 65 81) !important; /* dark:bg-gray-700 */
        border-color: rgb(75 85 99) !important; /* dark:border-gray-600 */
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    /* Textarea en dark */
    .dark .fi-textarea textarea {
        background-color: rgb(55 65 81) !important; /* dark:bg-gray-700 */
        border-color: rgb(75 85 99) !important; /* dark:border-gray-600 */
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    /* === MEJORAS PARA COMPATIBILIDAD === */
    /* Asegurar que los enlaces funcionen bien en ambos modos */
    .dark a {
        color: rgb(96 165 250) !important; /* dark:text-blue-400 */
    }

    .dark a:hover {
        color: rgb(147 197 253) !important; /* dark:text-blue-300 */
    }
    </style>
</div>