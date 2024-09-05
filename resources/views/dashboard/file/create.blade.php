<form method="POST" action="{{ route('filemanager.file.store') }}" class="formajax_refresh_popup"
    enctype="multipart/form-data">
    @csrf
    <x-input type="text" class="form-control" name="path" value="{{ $path }}" hidden />
    <div class="form-group">
        <label for="file">{{ __('File Name') }}</label>
        <x-input type="text" class="form-control w-full " id="name" name="name" required />
    </div>
    <div class="form-group">
        <label for="file">{{ __('Code') }}</label>

        <div class="relative">
            <x-textarea is="highlighted-code" cols="80" rows="12" language="php" tab-size="2" name="content"
                auto-height>console.log("highlighted code textarea");</x-textarea>
            <script type="module" defer>
                (async ({
                    chrome,
                    netscape
                }) => {
                    if (!chrome && !netscape) await import('https://unpkg.com/@ungap/custom-elements');
                    const {
                        default: HighlightedCode
                    } = await import('https://unpkg.com/highlighted-code');
                    HighlightedCode.useTheme('github');

                })(self);
            </script>

        </div>

    </div>
    <x-button type="submit" class="btn-primary">{{ __('Create') }}</x-button>
</form>
