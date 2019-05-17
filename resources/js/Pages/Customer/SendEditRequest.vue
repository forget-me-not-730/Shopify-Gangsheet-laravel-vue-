<script>
import {defineComponent} from 'vue'
import Spinner from "@/Components/Spinner.vue";
import BuilderLayout from "@/Layouts/BuilderLayout.vue";

export default defineComponent({
    name: "SendEditRequest",
    components: {BuilderLayout, Spinner},
    props: {
        design: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            loading: false,
            requestSent: false
        }
    },
    computed: {
        isIframe() {
            return window.top !== window.self
        }
    },
    methods: {
        handleSendRequest() {
            this.loading = true
            axios.post((route('builder.send-edit-request', this.design.id))).then(res => {
                this.loading = false
                this.requestSent = true
            })
        },

        handleClose() {
            if (this.isIframe) {
                window.parent.postMessage({
                    action: 'gs_close_builder'
                }, '*')
            } else {
                window.close()
            }
        }
    }
})
</script>

<template>
    <BuilderLayout title="Send Edit Request">
        <div class="flex h-full w-full justify-center items-center bg-white font-oswald">
            <div class="max-w-md mx-auto">
                <h2 class="text-3xl font-semibold text-gray-800">
                    <i class="mdi mdi-information-outline"></i> Send a request to edit
                </h2>

                <div class="mt-5 text-gray-600">
                    <p>
                        Editing a gang sheet after checkout is not permitted.
                    </p>

                    <p>
                        To edit your gang sheet, please click the button below to request access from the print shop owner.
                    </p>
                    <p>
                        This feature is the ensure that your correct artwork is printed.
                    </p>
                    <p>
                        Thank you for your understanding.
                    </p>
                </div>
                <div v-if="requestSent || design.edit_request === 'pending'" class="flex items-center mt-10 text-green-500">
                    <div class="circle-loader load-complete mr-2">
                        <div class="checkmark draw"></div>
                    </div>
                    <span class="font-normal text-lg">Your request sent successfully!</span>
                </div>
                <div v-else-if="design.edit_request === 'declined'" class="border border-red-500 rounded mt-5 py-2 px-4">
                    <div  class="flex items-center text-red-500 text-lg">
                        <i class="mdi mdi-information-outline mr-2"></i>
                        <span class="font-normal">Your request was declined by shop admin!</span>
                    </div>
                    <div v-if="design.decline_reason" class="mt-5">
                        <label>Reason for Decline</label>
                        <p class="font-light mt-1">
                            {{design.decline_reason}}
                        </p>
                    </div>
                </div>
                <button v-else :disabled="loading" @click="handleSendRequest" class="flex items-center bg-primary text-white rounded py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2 mt-10">
                    <spinner v-if="loading" class="mr-2"/>
                    <span>Send Edit Request</span>
                </button>

                <button @click="handleClose" class="btn-builder mt-5">
                    <span>Close</span>
                </button>
            </div>
        </div>
    </BuilderLayout>
</template>

<style lang="scss" scoped>
$brand-success: #5cb85c;
$loader-size: 3em;
$check-height: calc($loader-size / 2);
$check-width: calc($check-height / 2);
$check-left: calc(calc($loader-size / 6) + calc($loader-size / 12));
$check-thickness: 3px;
$check-color: $brand-success;

.circle-loader {
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-left-color: $check-color;
    animation: loader-spin 1.2s infinite linear;
    position: relative;
    display: inline-block;
    vertical-align: top;
    border-radius: 50%;
    width: $loader-size;
    height: $loader-size;
}

.load-complete {
    -webkit-animation: none;
    animation: none;
    border-color: $check-color;
    transition: border 500ms ease-out;
    color: $check-color;
}

.checkmark {

    &.draw:after {
        animation-duration: 800ms;
        animation-timing-function: ease;
        animation-name: checkmark;
        transform: scaleX(-1) rotate(135deg);
    }

    &:after {
        opacity: 1;
        height: $check-height;
        width: $check-width;
        transform-origin: left top;
        border-right: $check-thickness solid $check-color;
        border-top: $check-thickness solid $check-color;
        content: '';
        left: $check-left;
        top: $check-height;
        position: absolute;
    }
}

@keyframes loader-spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes checkmark {
    0% {
        height: 0;
        width: 0;
        opacity: 1;
    }
    20% {
        height: 0;
        width: $check-width;
        opacity: 1;
    }
    40% {
        height: $check-height;
        width: $check-width;
        opacity: 1;
    }
    100% {
        height: $check-height;
        width: $check-width;
        opacity: 1;
    }
}
</style>
