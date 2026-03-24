<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>
        @isset($title)
            {{ $title }}
        @endisset
    </title>
    @isset($extraStyle)
        {{ $extraStyle }}
    @endisset
</head>

<body class="bg-transparent">

    <!-- NAVBAR -->
    <!-- <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">

                <div class="flex items-center gap-3">
                    <button id="menuBtn" class="md:hidden text-gray-600 hover:text-indigo-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="flex items-center gap-2">
                        <div
                            class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold">
                            G
                        </div>
                        <span class="font-semibold text-gray-800"></span>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-6">
                    <a href="#" class="text-gray-700 hover:text-indigo-600">Dashboard</a>
                    <a href="#" class="text-gray-700 hover:text-indigo-600">Modules</a>
                    <a href="#" class="text-gray-700 hover:text-indigo-600">Sécurité</a>
                </div>

                <div class="relative">
                    <button id="profileBtn" class="flex items-center gap-2 focus:outline-none">
                        <div
                            class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                            213devops
                        </div>
                        <i class="fas fa-chevron-down text-gray-500 hidden md:block"></i>
                    </button>

                    <div id="profileMenu"
                        class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-lg border border-gray-100">
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-50">Profil</a>
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-50">Sécurité</a>
                        <div class="border-t my-1"></div>
                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-white border-t">
            <a href="#" class="block px-4 py-3 hover:bg-gray-50">Dashboard</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-50">Modules</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-50">Sécurité</a>
        </div>
    </nav> -->

    @isset($content)
        {{ $content }}
    @endisset

    <script>
        document.getElementById('menuBtn').onclick = () =>
            document.getElementById('mobileMenu').classList.toggle('hidden');

        document.getElementById('profileBtn').onclick = () =>
            document.getElementById('profileMenu').classList.toggle('hidden');

        document.addEventListener('click', e => {
            if (!e.target.closest('#profileBtn')) {
                document.getElementById('profileMenu').classList.add('hidden');
            }
        });
    </script>

    <script src="{{ asset('ssoauth/js/sso-client.js') }}"></script>

    @isset($extraJs)
        <script>
            {{ $extraJs }}
        </script>
    @endisset

</body>

</html>