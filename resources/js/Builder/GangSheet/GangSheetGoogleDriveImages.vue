<script>
import {defineComponent} from 'vue'
import eventBus from "@/Builder/Utils/eventBus";
import {getSessionId} from '@/Builder/Utils/helpers'
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import GoogleDriveIcon from '@/Builder/Icons/GoogleDriveIcon.vue';
import Spinner from "@/Builder/Components/Spinner.vue";

export default defineComponent({
    name: "GangSheetGoogleDrive",
    components: {GoogleDriveIcon, Spinner},
    mixins: [gangSheetMixin],
    data() {
        return {
            connectingGoogleDrive: false,
            disconnectingGoogleDrive: false,
            connectedGoogleDrive: false,
        }
    },
    mounted() {
        this.getGoogleDriveStatus();
    },
    methods: {
        getGoogleDriveStatus() {
            window.axios
                .get(route('google-auth.authorize-url', {
                    customer_id: this.customer ? this.customer?.id : null,
                    session_id: getSessionId(),
                }))
                .then((res) => {
                    if (res.data.success) {
                        this.googleDrive.authorize_url = res.data.authorize_url;
                        this.googleDrive.accessToken = res.data.token;
                        this.connectedGoogleDrive = Boolean(res.data.token);
                    }
                })
                .finally(() => {
                    this.connectingGoogleDrive = false;
                    this.disConnectingGoogleDrive = false;
                });
        },
        handleConnectGoogleDrive() {
            const popupWinWidth = 600;
            const popupWinHeight = 600;
            const left = (screen.width - popupWinWidth) / 2;
            const top = (screen.height - popupWinHeight) / 4;
            if (this.googleDrive.authorize_url) {
                this.connectingGoogleDrive = true;
                window._googleDriveWindow = window.open(
                    this.googleDrive.authorize_url,
                    'Connect Google Drive',
                    `popup=yes,height=${popupWinHeight},width=${popupWinWidth},top=${top},left=${left}`
                );
                if (window.focus) {
                    window._googleDriveWindow.focus();
                }
                const timer = setInterval(() => {
                    if (window._googleDriveWindow.closed) {
                        clearInterval(timer);
                        this.getGoogleDriveStatus();
                    }
                }, 1000);
            } else {
                this.loadPicker();
            }
        },
        handleDisconnectGoogleDrive() {
            this.disConnectingGoogleDrive = true;
            axios.get(
                route('google-auth.disconnect', {
                    customer_id: this.customer ? this.customer?.id : null,
                    session_id: getSessionId(),
                }))
                .then(() => {
                    this.getGoogleDriveStatus();
                });
        },
        loadPicker() {
            if (!this.googleDrive.accessToken) return;
            const script = document.createElement('script');
            script.src = 'https://apis.google.com/js/api.js';
            script.onload = () => {
                gapi.load('picker', {
                    callback: () => this.handleDrivePicker(),
                });
            };
            document.body.appendChild(script);
        },
        handleDrivePicker() {
            const picker = new google.picker.PickerBuilder()
                .addView(google.picker.ViewId.DOCS)
                .setOAuthToken(this.googleDrive.accessToken.access_token)
                .setCallback((data) => this.pickerCallback(data))
                .enableFeature(google.picker.Feature.NAV_HIDDEN)
                .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                .build();
            picker.setVisible(true);
        },
        async pickerCallback(data) {
            if (data[google.picker.Response.ACTION] === google.picker.Action.PICKED) {
                const documents = data[google.picker.Response.DOCUMENTS];
                const fetchedImages = await Promise.all(
                    documents.map(async (doc, index) => {
                        const url = `https://www.googleapis.com/drive/v3/files/${doc.id}?alt=media`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                Authorization: `Bearer ${this.googleDrive.accessToken.access_token}`,
                            },
                        });
                        if (!response.ok) {
                            console.error('Photo fetching error');
                            return null;
                        }
                        const blob = await response.blob();
                        const file = new File([blob], doc.name, {
                            type: blob.type,
                        });
                        return {
                            id: new Date().getTime() + index,
                            file,
                            url: URL.createObjectURL(file),
                        };
                    })
                );
                const sharedImages = fetchedImages.filter((image) => image !== null);
                sharedImages.forEach((newImage) => {
                    this.images.unshift(newImage);
                });
            }
        }
    }
})
</script>

<template>
    <div class="flex flex-col h-full w-full items-center justify-center">
        <div v-if="googleDrive.accessToken" class="flex flex-col text-xs px-2 w-full max-w-md">
            <div class="flex justify-between mt-2">
                <div class="flex items-center space-x-2">
                    <span
                        class="bg-orange-600 w-8 h-8 text-xl flex items-center justify-center rounded-full text-white">
                        {{ googleDrive.accessToken.name[0] }}
                    </span>
                    <div class="flex flex-col">
                        <span class="text-gray-900">
                            {{ googleDrive.accessToken.email }}
                        </span>
                        <span class="font-medium text-gray-900">
                            {{ googleDrive.accessToken.name }}
                        </span>
                    </div>
                </div>
                <button
                    class="btn-builder btn-sm text-xs"
                    @click.prevent="handleDisconnectGoogleDrive"
                >
                <spinner v-if="disconnectingGoogleDrive" />
                    Disconnect
                </button>
            </div>
        </div>

        <div class="flex cursor-pointer items-center justify-center py-4" @click="handleConnectGoogleDrive" :disabled="connectingGoogleDrive">
            <google-drive-icon size="30" class="mr-2"/>
            <button class="btn-builder w-56">
                <spinner v-if="connectingGoogleDrive"/>
                {{ googleDrive.accessToken ? "Use Google Drive" : "Connect with Google Drive" }}
            </button>
        </div>
    </div>
</template>
