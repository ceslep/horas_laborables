<script lang="ts">
  let { value = $bindable(""), id = "" } = $props();

  const teachers = [
    "ANA SOFIA CARDENAS PETUMA",
    "BLANCA ILDORY JARAMILLO JARAMILLO",
    "BLANCA NELLY MARIN GIRALDO",
    "CARLOS ALBERTO AGUIRRE GONZALEZ",
    "CARLOS ARMANDO RIOS ALVAREZ",
    "CARLOS ARTURO CUARTAS",
    "CARMEN ROCIO ALVAREZ SOLARTE",
    "CESAR LEANDRO PATIÑO VELEZ",
    "DANIEL QUICENO RAMIREZ",
    "DELCID DE JESUS BUENO ARANDIA",
    "DERLY JOHANNA PUERTAS ALVAREZ",
    "DIANA MARCELA ROMERO GAMBOA",
    "ESTEBAN FERNANDO JARAMILLO MUÑOZ",
    "ISABEL CRISTINA GOMEZ MONTOYA",
    "JAVIER DE JESUS AGUDELO CARVAJAL",
    "JHON JAIRO VELEZ TEJADA",
    "JOHN EDWIN ARBOLEDA ACEVEDO",
    "JON JAMES VASCO RODRIGUEZ",
    "JOSE FERNANDO CARDONA HENAO",
    "LEON FERNEY CARO ARDILA",
    "PABLO ESTEBAN REINOSO HENAO",
  ];
</script>

<select
  {id}
  bind:value
  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none cursor-pointer"
>
  <option value="" disabled selected>Seleccione un docente...</option>
  {#each teachers as teacher}
    <option value={teacher}>{teacher}</option>
  {/each}
</select>

<style>
  select option {
    background: white;
    color: #1a1a1a;
  }
</style>
