<!--
<form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 flex-wrap" id="filtro-etapas-form">
    @php
        $selected = request('etapas', []);
    @endphp

    @foreach ($fases as $id => $label)
        @php
            $count = $counts->get($id, 0);
            $isSelected = in_array($id, $selected);
        @endphp
        <label
            style="{{ $isSelected
                ? 'background-color: #fbbf24; color: #1a202c; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'
                : '' }}"
            class="cursor-pointer px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors duration-200
                {{ $isSelected ? '' : 'bg-gray-100 text-gray-700 hover:bg-primary-100 dark:bg-gray-800 dark:text-white dark:hover:bg-primary-600' }}"
        >


            <input type="checkbox" name="etapas[]" value="{{ $id }}" class="hidden" {{ $isSelected ? 'checked' : '' }}>
            {{ $label }} <span class="text-gray-500 font-normal">({{ $count }})</span>
        </label>
    @endforeach
</form>

<script>
    document.querySelectorAll('#filtro-etapas-form input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            document.getElementById('filtro-etapas-form').submit();
        });
    });
</script>
-->
