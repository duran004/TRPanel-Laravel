{{-- folder name --}}
<form method="POST" action="{{ route('filemanager.folder.store') }}" class="formajax_refresh_popup">
    @csrf
    <div class="form-group">
        <label for="name">{{ __('Folder Name') }}</label>
        <x-input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Folder Name') }}"
            required> </x-input>
        <x-input type="text" class="form-control" name="path" value="{{ $path }}" hidden />
    </div>
    <x-button type="submit" class="btn-primary">{{ __('Create') }}</x-button>
</form>
