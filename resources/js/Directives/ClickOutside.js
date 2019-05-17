const clickOutsideDirective = (app) => {
    app.directive("click-outside", {
        beforeMount: function (el, binding) {
            el.__vueClickEventHandler__ = (event) => {
                if (!el.contains(event.target) && el !== event.target) {
                    if (typeof binding.value === 'function') {
                        binding.value(event)
                    }
                }
            }
            document.body.addEventListener('click', el.__vueClickEventHandler__)
        },
        unmounted: function (el) {
            document.body.removeEventListener('click', el.__vueClickEventHandler__)
        }
    });
};

export default clickOutsideDirective;
