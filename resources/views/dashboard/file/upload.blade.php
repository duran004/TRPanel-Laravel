<form method="POST" action="{{ route('filemanager.file.store_upload') }}" class="formajax_refresh_popup"
    enctype="multipart/form-data">
    @csrf
    <x-input type="text" class="form-control" name="path" value="{{ $path }}" hidden />
    <div class="form-group">
        <label for="file">{{ __('File') }}</label>
        <x-input type="file" class="form-control w-full " id="file" name="file" required />
    </div>

    <x-button type="submit" class="btn-primary">{{ __('Upload') }}</x-button>
</form>
