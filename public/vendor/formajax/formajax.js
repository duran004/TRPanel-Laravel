class FormAjax {
    constructor(formElement) {
        this.debug = true;
        this.dependencies = ['jquery', 'jquery-confirm'];
        this.elementsSelector = 'input, select, textarea, checkbox, radio, button, submit, reset, hidden, password, text, url, email, tel, date, datetime-local, month, week, time, number, range, color';
        this.formElement = formElement;
        this.settings = new FormSettings();
        this.popupSize = 'dcdc w-8/12 mx-auto';
        this.laravelPath = '/vendor/formajax/';
        this.csrfToken = null;
        this.loadDependencies().then(() => this.init());
    }
    async loadDependencies() {
        const missingDependencies = this.dependencies.filter(dep => {
            if (dep === 'jquery' && typeof jQuery === 'undefined') {
                return true;
            }
            if (dep === 'jquery-confirm' && (typeof $ === 'undefined' || typeof $.confirm === 'undefined')) {
                return true;
            }
            return false;
        });

        if (missingDependencies.length > 0) {
            for (const dep of missingDependencies) {
                this.log(`${dep} not found`, 'error');
                if (dep === 'jquery') {
                    await this.loadScript(`${this.laravelPath}jquery.js`);
                }
                if (dep === 'jquery-confirm') {
                    await this.loadScript(`${this.laravelPath}jquery-confirm.js`);
                    await this.loadScript(`${this.laravelPath}jquery-confirm.css`);
                }
            }
            return this.loadDependencies();
        }

        return true;
    }

    loadScript(file) {
        return new Promise((resolve, reject) => {
            this.log(`Loading ${file}`);
            const is_js_or_css = file.split('.').pop();
            let element;
            if (is_js_or_css === 'css') {
                element = document.createElement('link');
                element.rel = 'stylesheet';
                element.type = 'text/css';
                element.href = file;
            } else {
                element = document.createElement('script');
                element.src = file;
            }
            element.onload = () => {
                this.log(`${file} loaded successfully`);
                resolve();
            };
            element.onerror = (e) => {
                this.log(e, 'error');
                reject(e);
            };
            document.getElementsByTagName('head')[0].appendChild(element);
        });
    }

    init() {
        $(document).on('submit', this.formElement, (e) => {
            e.preventDefault();
            this.log(`The form has been submitted: ${this.formElement}`);
            this.handleSubmit(e.target);
        });
        this.log(`form_element: ${this.formElement}`);
    }

    handleSubmit(form) {
        this.popupSize = $(form).data('modal-size') || this.popupSize;
        this.log(`popup_size: ${this.popupSize}`);
        if (this.settings.confirm) {
            $.confirm({
                title: this.settings.confirmTitle,
                content: this.settings.confirmMsg,
                onOpenBefore: function () {
                    $('.jconfirm-row').addClass('inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50');
                    $('.jconfirm-holder').addClass('flex items-center justify-center');
                },

                buttons: {
                    Evet: {
                        btnClass: 'btn-blue jquery_confirm_btn_blue',
                        action: () => this.submit(form),
                    },
                    Hayir: {
                        btnClass: 'btn-red',
                        action: () => $.alert({
                            title: 'İptal Edildi',
                            content: 'İşlem iptal edildi.',
                            type: 'red',
                            columnClass: this.popup, onOpenBefore: function () {
                                $('.jconfirm-row').addClass('inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50');
                                $('.jconfirm-holder').addClass('flex items-center justify-center');
                            },
                            backgroundDismiss: true,
                        }),
                    },
                },



            });
        } else {
            this.submit(form);
        }
    }

    setFormSettings(settings) {
        this.settings = settings;
    }

    getElements(form) {
        return [...form.elements];
    }

    createUrl(form) {
        const allElements = $(form).find(this.elementsSelector);
        return allElements.toArray().reduce((url, element) => {
            const $element = $(element);
            const name = $element.attr('name');
            if (name) {
                let value;
                if ($element.is(':checkbox')) {
                    value = $element.prop('checked') ? 'true' : 'false';
                } else {
                    value = $element.val();
                }
                this.log(`name: ${name} value: ${value}`);
                url += `${encodeURIComponent(name)}=${encodeURIComponent(value)}&`;
            }
            return url;
        }, '').slice(0, -1); // Remove the last '&' character
    }
    submit(form) {
        var formData = new FormData();
        const formUrl = $(form).attr('action');
        const formMethod = $(form).attr('method').toLowerCase();
        const allElements = $(form).find(this.elementsSelector);
        const checkboxNames = new Set();
        var _method = $(form).find('input[name="_method"]').val();
        var csrfInput = $(form).find('input[name="_token"]');
        this.csrfToken = csrfInput.length ? csrfInput.val() : null;
        const submitButton = $(form).find('button[type="submit"]');
        const loader_icon = '<i class="fa fa-spinner fa-spin"></i>';
        this.log(`form_url: ${formUrl} 
            form_method: ${formMethod} 
            csrf_token: ${this.csrfToken}`);

        allElements.each((_, element) => {
            const $element = $(element);
            if ($element.is(':checkbox')) {
                const name = $element.attr('name');
                checkboxNames.add(name);
            } else if ($element.is(':file')) {
                // Handle file inputs
                const files = $element[0].files;
                for (let i = 0; i < files.length; i++) {
                    formData.append($element.attr('name'), files[i]);
                }
            } else {
                formData.append($element.attr('name'), $element.val());
            }
        });

        checkboxNames.forEach(name => {
            $(form).find(`input[name="${name}"]:checked`).each((_, el) => {
                formData.append(name, $(el).val()); // Append each checked value
            });
        });

        // Log the contents of formData
        console.log('form_data:');
        for (let pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }
        // submitbuttonu disable yap tüm form içerisinde gri yap loading bar koy
        submitButton.prop('disabled', true);
        var submitButtonText = submitButton.html();
        submitButton.html(loader_icon);


        $.ajax({
            url: formUrl,
            method: formMethod,
            data: formData,
            processData: false, // Do not process data
            contentType: false, // Do not set content type

            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'X-HTTP-Method-Override': _method // Add the X-HTTP-Method-Override header
            },
            success: (response, status, xhr) => {
                submitButton.prop('disabled', false);
                submitButton.html(submitButtonText);
                console.log(response);
                //if status start with 2
                if (xhr.status.toString().startsWith('2')) {
                    if (response.status) {
                        this.handleSuccess(response);
                    } else {
                        this.handleError(response);
                    }
                } else if (xhr.status.toString().startsWith('4')) {
                    this.handleError(response, 400);
                } else if (xhr.status.toString().startsWith('5')) {
                    this.handleError(response, 500);
                } else if (xhr.status.toString().startsWith('3')) {
                    this.handleError(response, 300);
                } else if (xhr.status.toString().startsWith('1')) {
                    this.handleError(response, 100);
                } else {
                    this.handleError(xhr);
                }
            },
            error: (xhr) => {
                submitButton.prop('disabled', false);
                submitButton.html(submitButtonText);

                console.log(xhr);
                this.handleError(xhr);
            }
        });
    }
    handleSuccess(response) {
        if (this.settings.openPopup) {
            $.alert({
                columnClass: this.popupSize + ' col-md-offset-3',
                animationSpeed: 0,
                backgroundDismiss: true,
                title: this.settings.title,
                content: response.message,
                closeIcon: true,
                type: 'orange',
                onOpenBefore: function () {
                    $('.jconfirm-row').addClass('inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50');
                    $('.jconfirm-holder').addClass('flex items-center justify-center');
                },

                buttons: {
                    Tamam: {
                        isHidden: true,
                        btnClass: 'btn-blue jquery_confirm_btn_blue',
                        action: () => {
                            if (this.settings.refresh) {
                                setTimeout(() => location.reload(), this.settings.refreshTime);
                            }
                        },
                    },
                },
            });
        }
        if (this.settings.refresh) {
            setTimeout(() => location.reload(), this.settings.refreshTime);
        }
    }

    handleError(xhrOrMsg, type = 'error') {
        console.log(xhrOrMsg);
        let errorMessage = '';
        if (type === 200) {
            errorMessage = xhrOrMsg.message;
        } else {
            errorMessage = xhrOrMsg.responseJSON ? xhrOrMsg.responseJSON.message : xhrOrMsg.statusText;
        }


        if (this.settings.openPopup) {
            $.alert({
                columnClass: this.popupSize + ' col-md-offset-3',
                title: 'Error',
                content: errorMessage,
                onOpenBefore: function () {
                    $('.jconfirm-row').addClass('inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50');
                    $('.jconfirm-holder').addClass('flex items-center justify-center');
                },
                backgroundDismiss: true,
                buttons: {
                    Tamam: {
                        btnClass: 'btn-red',
                    },
                },
            });
        }
    }

    log(message, type = 'info') {
        if (!this.debug) return;

        const logTypes = {
            info: { background: '#007bff', color: 'white' },
            error: { background: '#dc3545', color: 'white' },
            warning: { background: '#ffc107', color: 'black' },
            success: { background: '#28a745', color: 'white' },
        };

        const { background, color } = logTypes[type] || logTypes.info;
        console.log(`%c FormAjax: ${message}`, `background: ${background}; color: ${color}; padding: 2px; border-radius: 5px;`);
    }
}

class FormSettings {
    constructor({
        title = 'İşlem Sonucu',
        openPopup = false,
        confirm = false,
        confirmTitle = 'Uyarı!',
        confirmMsg = 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?',
        refresh = false,
        refreshTime = 1000,
    } = {}) {
        this.title = title;
        this.openPopup = openPopup;
        this.confirm = confirm;
        this.confirmTitle = confirmTitle;
        this.confirmMsg = confirmMsg;
        this.refresh = refresh;
        this.refreshTime = refreshTime;
    }
}

// Usage
const formajax = new FormAjax('.formajax');
formajax.setFormSettings(new FormSettings({}));

const formajax_refresh = new FormAjax('.formajax_refresh');
formajax_refresh.setFormSettings(new FormSettings({ refresh: true }));

const formajax_confirm = new FormAjax('.formajax_confirm');
formajax_confirm.setFormSettings(new FormSettings({ confirm: true, confirmMsg: 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?' }));

const formajax_delete = new FormAjax('.formajax_delete');
formajax_delete.setFormSettings(new FormSettings({ confirm: true, confirmMsg: 'Bu işlem geriye alınamaz onaylıyor musunuz?', refresh: true, refreshTime: 2000, title: 'Silme Sonucu', openPopup: true }));

const formajax_edit = new FormAjax('.formajax_edit');
formajax_edit.setFormSettings(new FormSettings({ openPopup: true, title: 'Düzenleme Sonucu' }));

const formajax_view = new FormAjax('.formajax_view');
formajax_view.setFormSettings(new FormSettings({ openPopup: true, title: 'Görüntüleme Sonucu' }));

const formajax_popup = new FormAjax('.formajax_popup');
formajax_popup.setFormSettings(new FormSettings({ openPopup: true, title: '', confirm: false }));

const formajax_refresh_popup = new FormAjax('.formajax_refresh_popup');
formajax_refresh_popup.setFormSettings(new FormSettings({ openPopup: true, title: '', refresh: true, refreshTime: 2000 }));

