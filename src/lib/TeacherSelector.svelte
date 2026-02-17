<script lang="ts">
  import { API_CONFIG } from './constants.js';
  
  let { value = $bindable(""), id = "" } = $props();

  let teachers = $state([]);
  let loading = $state(true);
  let error = $state<string | null>(null);

  async function fetchTeachers() {
    try {
      loading = true;
      error = null;
      const response = await fetch(API_CONFIG.profesURL);
      if (!response.ok) {
        throw new Error(`Error fetching teachers: ${response.status}`);
      }
      teachers = await response.json();
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to fetch teachers:', err);
    } finally {
      loading = false;
    }
  }

  fetchTeachers();
</script>

<select
  {id}
  bind:value
  class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none cursor-pointer"
  disabled={loading}
>
  <option value="" disabled selected>
    {loading ? 'Cargando docentes...' : 'Seleccione un docente...'}
  </option>
  {#if error}
    <option value="" disabled>Error: {error}</option>
  {:else}
    {#each teachers as teacher}
      <option value={teacher}>{teacher}</option>
    {/each}
  {/if}
</select>

<style>
  select option {
    background: white;
    color: #1a1a1a;
  }
</style>
