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
                <div class="mb-6">
                <a href="{{ route('incident.create') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                {{ __('Add new Incident') }}
                </a>
                </div>  
                
                <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3">ID</th>
                            <th scope="col" class="px-6 py-3">Reporter</th>
                            <th scope="col" class="px-6 py-3">Type</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidents as $incident)
                        <tr>
                        <td class="px-6 py-4">{{ $incident->id}}</td>
                        <td class="px-6 py-4">{{ $incident->reporter}}</td>
                        <td class="px-6 py-4">{{ $incident->type}}</td>
                        <td class="px-6 py-4">{{ $incident->status}}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('incident.edit', $incident->id) }}">{{ __('Edit') }}</a>
                            <form action="{{ route('incident.destroy',$incident->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-meduim text-red-600 hover:underline">
                            {{ __('Delete') }}
                            </button>
                            </form>

                        </td>

                        </tr>
                        @endforeach
                    </tbody>

                </table>

                </div>



                </div>
            </div>
        </div>
    </div>
</x-app-layout>