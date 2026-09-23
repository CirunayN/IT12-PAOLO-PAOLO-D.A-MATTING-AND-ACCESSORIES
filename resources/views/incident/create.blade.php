<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Incident') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                <form action="{{ route('incident.store') }}" method="POST">
                @csrf
                <div>
                <x-input-label for="reporter" :value="__('Reporter')" />
                <x-text-input id="reporter" class="block mt-1 w-full" type="text" name="reporter" :value="old('reporter name')" required autofocus autocomplete="reporter" />
                <x-input-error :messages="$errors->get('reporter')" class="mt-2" />
                </div>

                 <div>
                <x-input-label for="type" :value="__('Type')" />
                <x-text-input id="type" class="block mt-1 w-full" type="text" name="type" :value="old('Type')" required autofocus autocomplete="type" />
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                <x-input-label for="status" :value="__('Status')" />
                <x-text-input id="status" name="status" type="text" class="mt-1 block w-full" :value="old('status')" required />
                <x-input-error class="mt-2" :messages="$errors->get('status')" />

                </div>
                <br>
                <div>
                <x-primary-button class="ms-4">
                    {{ __('Save Incident') }}
                </x-primary-button>
                <a href="{{ route('incident.index') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                {{ __('Cancel') }}
                </a>
                </div>

                </form>


                </div>
            </div>
        </div>
    </div>
</x-app-layout>