<script lang="ts">
  let { value = $bindable(""), id = "" } = $props();

  const teachers = [
    "AIDA MILENA RIVERA",
    "ANA MILENA FERNÁNDEZ BOLAÑOS",
    "ANA SOFIA CARDENAS PETUMA",
    "BLANCA NELLY MARÍN GIRALDO",
    "CARLOS ALBERTO AGUIRRE GONZÁLEZ",
    "CARLOS ARMANDO RÍOS ÁLVAREZ",
    "CARLOS ARTURO CUARTAS RAMIREZ",
    "CESAR LEANDRO PATIÑO VELEZ",
    "DANIEL QUICENO RIVERA",
    "DANNY DANIEL QUINTERO GIRALDO",
    "DELCID DE JESÚS BUENO ARANDIA",
    "DERLY JOANNA PUERTAS",
    "DIANA LISBETH VÁSQUEZ LONDOÑO",
    "DIANA MARCELA ROMERO GAMBOA",
    "ESTEBAN FERNANDO JARAMILLO MUÑOZ",
    "FANNY ELENA MENA R",
    "GERMAN ALONSO CASTRILLÓN MARTÍNEZ",
    "GLORIA LILIANA QUINTERO OROZCO",
    "ILDORY JARAMILLO",
    "ISABEL CRISTINA GOMEZ MONTOYA",
    "JAVIER DE JESÚS AGUDELO CARVAJAL",
    "JESÚS ALBERTO MEJÍA RÍOS",
    "JHON JAIRO VÉLEZ TEJADA",
    "JOHN EDWIN ARBOLEDA ACEVEDO",
    "JON JAMES VASCO RODRÍGUEZ",
    "JOSE FERNANDO CARDONA HENAO",
    "LEON FERNEY CARO ARDILA",
    "MARÍA DEL CARMEN SANTA",
    "MARIA ELIZABETH GUERRERO R",
    "MARTA LIDA CASTAÑO OSORIO",
    "NELSON LONDOÑO SOTO",
    "ONEIDA JARAMILLO OCAMPO",
    "PABLO REINOSO HENAO",
  ];
</script>

<select
  {id}
  bind:value
  class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none cursor-pointer"
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
