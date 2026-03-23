<x-ssoauth-layout-main title="Connexion">
    <x-slot name="title">Profil</x-slot>
    <x-slot name="content">

        <div class="max-w-5xl mx-auto p-6 space-y-6">

            <!-- USER HEADER -->
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-6">
                <div
                    class="w-20 h-20 rounded-full bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($user->Prenom, 0, 1)) }}
                </div>

                <div>
                    <h2 class="text-xl font-bold">{{ $user->Prenom }}</h2>
                    <p class="text-gray-500">{{ $user->Email }}</p>
                </div>
            </div>

            <!-- MATCHING DEVICES -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-semibold mb-4">
                    Appareils & navigateurs reconnus
                </h3>

                <div class="divide-y">

                    @foreach ($user->matchings ?? [] as $matching)
                        <div class="py-4 flex items-center justify-between">

                            <div class="flex items-center gap-4">

                                <!-- DEVICE ICON -->
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    @if (Str::contains(strtolower($matching->device), 'mobile'))
                                        <i class="fas fa-mobile-alt text-purple-500"></i>
                                    @else
                                        <i class="fas fa-desktop text-indigo-500"></i>
                                    @endif
                                </div>

                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $matching->platform ?? 'Unknown OS' }}
                                        {{ $matching->version ? '– ' . $matching->version : '' }}
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        {{ $matching->device ?? 'Unknown device' }}
                                        · IP {{ $matching->ip_request }}
                                    </p>

                                    <p class="text-xs text-gray-400 truncate max-w-md">
                                        {{ $matching->user_agent }}
                                    </p>
                                </div>
                            </div>

                            <!-- STATUS -->
                            <div class="text-right">
                                @if ($matching->confirmed_at)
                                    <span class="px-3 py-1 mb-3 text-xs bg-green-100 text-green-700 rounded-full font-semibold">
                                        Appareil confirmé
                                    </span>
                                    <br>
                                    <span
                                        class="min-w-[150px] cursor-pointer my-4 px-6 py-1 text-xs bg-red-100 text-red-700 rounded-full font-semibold"
                                        onclick="return confirm('Supprimer cet appareil ?');">
                                        <i class="fas fa-trash mr-2"></i>
                                        Supprimer
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full font-semibold">
                                        En attente
                                    </span>
                                @endif

                                <p class="text-xs text-gray-400 mt-1">
                                    Ajouté le {{ $matching->created_at }}
                                </p>
                            </div>

                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </x-slot>

    <x-slot name="extraJs">
        window.parent.document.getElementById('ssoModal').classList.add('hidden');
        window.parent.document.getElementById('ssoIframe').src = '';

        setTimeout(() => {
        window.parent.postMessage({
        type: 'close-and-redirect',
        url: '/'
        }, '*')}, 300);

    </x-slot>
</x-ssoauth-layout-main>