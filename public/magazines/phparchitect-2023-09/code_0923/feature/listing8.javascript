<script setup lang="ts">
import { useRemember } from '@inertiajs/inertia-vue3'

const form = useRemember({
  title: '',
  name: '',
  email: '',
  more_fields: ''
}, 'optional-unique-id-if-mult-components-use-it')
</script>