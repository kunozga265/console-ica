<script setup>
import { onMounted, ref } from 'vue';
import InputLabel from './InputLabel.vue';
import { AutoComplete } from 'primevue';
  

const props = defineProps({
    modelValue: Object,
    label: String,

    items: Array,
    required: {
        type:Boolean,
        default:false
    },
});

defineEmits(['update:modelValue']);

const selected = ref(null);
const filtered = ref([]);


const search = (event) => {
    // console.log(event)
    if (!event.query.trim().length) {
        filtered.value = [...props.items];
    } else {
        filtered.value = props.items.filter((item) => {
            return item.name.toLowerCase().includes(event.query.toLowerCase());
        });
    }
      
}

const select = (event) => {
    console.log(event.value.id)
    model = event.value.id
}
</script>

<template>
    <div>
        <InputLabel v-if="label != null" :value="label" :required="required" />
        <div class="mt-1 focus-within:ring-ring flex overflow-hidden  bg-background text-sm ring-offset-background transition-[box-shadow,transform,opacity] duration-300 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 w-full"
            aria-describedby="form-item-description-#/properties/subject" aria-invalid="false">
            <AutoComplete class="w-full z-40" v-model="selected" dropdown forceSelection option-label="name"
                :suggestions="filtered" @complete="search"  @item-select="$emit('update:modelValue', $event.value)"/>
        </div>
    </div>
  
    <!-- <input ref="input" class="form-control" :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"> -->
</template>
