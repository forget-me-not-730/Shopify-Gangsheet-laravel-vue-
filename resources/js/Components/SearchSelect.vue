<script setup>
import {onMounted, ref, watch} from "vue";
import CloseButton from "@/Components/CloseButton.vue";

const props = defineProps({
    url: {
        type: String,
        default: '',
    },
    value: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    defaultValue: {
        type: Object,
        default: null,
    },
    updateState: {
        type: Function,
        default: () => {},
    },
    inputClass: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Search...'
    }
});

let keyword = ref(null)
let selected = ref(null)
let loading = ref(false)
let list = ref([])

watch(keyword, (value) => {
    loading.value = true
    axios.get(props.url + '?keyword=' + value)
        .then(response => {
            list.value = response.data
            loading.value = false
        })
        .catch(error => {
            console.log(error);
        });
})
const selectItem = (item) => {
    selected.value = item

    props.updateState(item[props.value])
}
const createDebounce = () => {
    let timeout = null;
    return function (fnc, delayMs) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fnc();
        }, delayMs || 500);
    };
}
const debounce = createDebounce();
const clearSelect = () => {
    selected.value = null
    props.updateState(null)
    //focus on input

}
onMounted(() => {
    selected.value = props.defaultValue
})
</script>

<template>
    <div>
        <div class="relative" v-if="!selected">
            <input :value="keyword" @input="debounce(() => { keyword = $event.target.value })" :placeholder="placeholder" :class="inputClass" class="block w-full rounded-full border-0 px-4 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-primary focus:outline-none sm:leading-6 disabled:bg-gray-300" />

            <ul v-if="keyword && !loading" class="absolute z-10 mt-1 max-h-60 w-full py-1 overflow-auto rounded-md bg-white text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" tabindex="-1">
                <li v-for="item in list" v-on:click="selectItem(item)" class="text-gray-900 relative cursor-pointer hover:bg-primary hover:text-white py-2 pl-3 pr-9">
                    <span class="font-normal block truncate font-semibold">{{ item[name] }}</span>
                    <span class="text-indigo-600 absolute inset-y-0 right-0 flex items-center pr-4" v-if="selected === item">
                      <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                      </svg>
                    </span>
                </li>
            </ul>
            <ul v-if="loading" class="absolute z-10 mt-1 max-h-60 w-full py-1 overflow-auto rounded-md bg-white text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" tabindex="-1">
                <li class="text-gray-900 relative cursor-pointer py-2 pl-3 pr-9">Loading...</li>
            </ul>
        </div>
        <div class="relative" v-if="selected">
            <input :value="selected[name]" readonly class="block w-full rounded-full border-0 px-4 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-primary focus:outline-none sm:leading-6 disabled:bg-gray-300" />
            <div class="absolute right-[8px] top-0 bottom-0 flex flex-col">
                <CloseButton v-on:click.prevent="clearSelect" class="my-auto"/>
            </div>
        </div>
    </div>
</template>
