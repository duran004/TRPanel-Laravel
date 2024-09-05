<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'bg-gray-300 hover:bg-gray-400 text-sm border-solid border-2 border-gray-200 text-gray font-bold py-1 px-1 rounded']) }}>
    {{ $slot }}
</button>
