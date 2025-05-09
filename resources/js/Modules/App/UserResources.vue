<script lang="ts" setup>
import { usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { numberFormat } from '@/Utils/format';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { is, can } from 'laravel-permission-to-vuejs'

const page = usePage();
const form = useForm({
  resource_id: null,
  amount: 1000
});

const resourcesData = ref(page.props.userResources || []);

const formattedResources = computed(() => {
  return resourcesData.value.map((resource) => {
    return {
      resource_id: resource.resource_id || null,
      name: resource.resource ? resource.resource.name : null,
      description: resource.resource ? resource.resource.description : null,
      image: resource.resource ? resource.resource.image : null,
      amount: computed(() => numberFormat(resource.amount)),
    };
  });
});

function addResource(resourceId) {
  if (!is('admin')) {
    return;
  }
  form.resource_id = resourceId;
  form.post('/resources/add', {
    onSuccess: () => {
      //
    },
  });
}

function updateResources(resources) {
  resourcesData.value.forEach((resourceData) => {
    const updatedResource = resources.find((res) => 
      parseInt(res.id) === parseInt(resourceData.resource_id)
    );
    if (updatedResource) {
      resourceData.amount = updatedResource.amount;
    }
  });
}

onMounted(() => {
  window.Echo.private(`user.update.${page.props.auth.user.id}`)
    .listen('.resources.updated', (data) => {
      if (data.resources) {
        updateResources(data.resources);
      }
    });
})
</script>

<template>
  <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-12 gap-1 w-max rounded-sm">
    <div class="relative group grid grid-cols-2 items-center p-2 border-base border rounded-md cursor-pointer"
      v-for="resource in formattedResources" :key="resource.name" @click="addResource(resource.resource_id)">
      <span class="flex items-center justify-center">
        <img :src="resource.image" class="max-h-5" />
      </span>
      <span class="text-sm font-medium text-white">
        {{ resource.amount }}
      </span>

      <AppTooltip :label="resource.name" position="bottom" class="!mt-3" />
    </div>
  </div>
</template>
