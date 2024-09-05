<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'bg-gray-300 hover:bg-gray-400 text-sm border-solid border-2 border-gray-200 text-gray font-bold py-1 px-1 rounded disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:bg-gray-300 disabled:border-gray-200 disabled:text-gray',
    ]) }}>
    {{ $slot }}
</a>
