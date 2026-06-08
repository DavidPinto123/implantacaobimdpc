<div class="flex gap-4 items-center">
    <div class="flex flex-col">
        <label for="pais_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">País</label>
        <select wire:model.defer="data.pais_id" id="pais_id"
            class="h-12 min-w-[200px] px-2 rounded-md border-gray-300 dark:bg-gray-800 dark:text-white">
            <option value="">Selecione</option>
            @foreach(\App\Models\Pais::orderBy('nome')->get() as $pais)
                <option value="{{ $pais->id }}">{{ $pais->nome }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col">
        <label for="estado_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
        <select wire:model.defer="data.estado_id" id="estado_id"
            class="h-12 min-w-[200px] px-2 rounded-md border-gray-300 dark:bg-gray-800 dark:text-white">
            <option value="">Selecione</option>
            @if (data_get($data ?? [], 'pais_id'))
                @foreach(\App\Models\Estado::where('pais_id', data_get($data, 'pais_id'))->orderBy('nome')->get() as $estado)
                    <option value="{{ $estado->id }}">{{ $estado->nome }}</option>
                @endforeach
            @endif
        </select>
    </div>

    <div class="flex flex-col">
        <label for="cidade_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
        <select wire:model.defer="data.cidade_id" id="cidade_id"
            class="h-12 min-w-[200px] px-2 rounded-md border-gray-300 dark:bg-gray-800 dark:text-white">
            <option value="">Selecione</option>
            @if (data_get($data ?? [], 'estado_id'))
                @foreach(\App\Models\Cidade::where('estado_id', data_get($data, 'estado_id'))->orderBy('nome')->get() as $cidade)
                    <option value="{{ $cidade->id }}">{{ $cidade->nome }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>
