{{--
    Production Machines modal: the modal sits inside the voucher form so its machine inputs are saved,
    but inside the page layout it ends up behind the backdrop. So while open it is moved to <body>,
    and it goes back to its place in the form when closed or before the form is submitted.
--}}
<style>
    #productionMachinesModal {
        z-index: 2000;
    }
    #productionMachinesModal .modal-dialog {
        max-width: min(1100px, calc(100vw - 2rem));
        margin: 1.5rem auto;
    }
    #productionMachinesModal .modal-content {
        border: 0;
        border-radius: 8px;
        overflow: hidden;
        max-height: calc(100vh - 3rem);
        display: flex;
        flex-direction: column;
    }
    #productionMachinesModal .modal-header {
        flex-shrink: 0;
        padding: 10px 16px;
        border-bottom: 1px solid #7fb3e6;
    }
    #productionMachinesModal .modal-header .modal-title {
        color: #000;
        font-size: 15px;
        font-weight: 600;
    }
    #productionMachinesModal .modal-header .close {
        margin: 0;
        padding: 0;
        width: 30px;
        height: 30px;
        line-height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.7);
        color: #1f2937;
        opacity: 1;
        text-shadow: none;
        font-size: 22px;
    }
    #productionMachinesModal .modal-header .close:hover {
        background: #ffffff;
    }
    /* Only the body scrolls, so the header and Close button stay in view */
    #productionMachinesModal .modal-body {
        background: #ffffff;
        padding: 16px 20px 4px;
        overflow-y: auto;
        flex: 1 1 auto;
    }
    /* The container had its own border/padding as a page section; inside the modal it is just content */
    #productionMachinesModal #productionMachinesContainer {
        border: 0 !important;
        padding: 0 !important;
    }
    #productionMachinesModal .modal-footer {
        flex-shrink: 0;
        padding: 10px 16px;
        background: #ffffff !important;
        border-top: 1px solid #dee2e6;
    }
    .modal-backdrop.production-machines-backdrop {
        z-index: 1999;
    }
</style>

<script>
    $(function () {
        const $modal = $('#productionMachinesModal');
        if (!$modal.length) {
            return;
        }

        // Marks the modal's home inside the form
        const $home = $('<span class="d-none production-machines-modal-home"></span>').insertBefore($modal);
        const $form = $home.closest('form');

        function moveBackToForm() {
            if (!$home.next().is($modal)) {
                $home.after($modal);
            }
        }

        $modal.on('show.bs.modal', function (e) {
            if (e.target !== this) return;
            $modal.appendTo('body');
        });

        $modal.on('shown.bs.modal', function (e) {
            if (e.target !== this) return;
            $('.modal-backdrop').last().addClass('production-machines-backdrop');
        });

        $modal.on('hidden.bs.modal', function (e) {
            if (e.target !== this) return;
            moveBackToForm();
        });

        // The machine inputs must be inside the form when it is sent
        $form.on('submit', moveBackToForm);
    });
</script>
