<script setup lang="ts">
import { useForm } from '@inertiajs/inertia-vue3'

const form = useForm({
  email: '',
  password: ''
})

const submit = async () => {
  form.post(route('login'), {
    preserveState: false,
    preserveScroll: true
  })
}
</script>