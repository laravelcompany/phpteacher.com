<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Inertia } from '@inertiajs/inertia'

const loaded = ref(false)

onMounted(() => {
   Inertia.reload();
   loaded.value = true;
})
</script>