<template>
    <merchant-layout title="Support">
        <div v-if="loading" class="absolute inset-0 flex justify-center items-center">
            <spinner/>
        </div>
        <div class="max-w-5xl mx-auto bg-white p-5 rounded-lg h-full shadow">
            <iframe title="Feedback Form" class="freshwidget-embedded-form" id="freshwidget-embedded-form"
                    @load="loading = false"
                    src="https://thedripapps.freshdesk.com/widgets/feedback_widget/new?&widgetType=embedded&formTitle=Submit+a+Ticket&submitTitle=Submit+Ticket&submitThanks=Thank+you+for+your+ticket.+Someone+from+our+support+staff+should+be+with+you+momentarily.&searchArea=no"
                    scrolling="no" height="100%" width="100%" frameborder="0">
            </iframe>
        </div>
    </merchant-layout>
</template>

<script>
import MerchantLayout from "@/Layouts/MerchantLayout.vue";
import Spinner from "@/Components/Spinner.vue";

export default {
    name: "Support",
    components: {Spinner, MerchantLayout},
    data() {
        return {
            loading: true
        }
    },
    mounted() {
        // Load Freshdesk widget JS
        const script = document.createElement('script');
        script.src = "https://s3.amazonaws.com/assets.freshdesk.com/widget/freshwidget.js";
        script.async = true;
        document.body.appendChild(script)
    }
}
</script>

