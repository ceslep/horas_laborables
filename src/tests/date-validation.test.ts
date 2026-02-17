import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock del contexto actual
const mockCurrentDate = new Date();

describe('isValidMonthToSave function', () => {
  const months = [
    "enero", "febrero", "marzo", "abril", "mayo", "junio",
    "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
  ];

  function isValidMonthToSave(selectedMonth: string, currentDate: Date): { valid: boolean; message: string } {
    const selectedMonthIndex = months.indexOf(selectedMonth);
    const currentMonth = currentDate.getMonth();
    const currentDay = currentDate.getDate();
    
    if (selectedMonthIndex === -1) {
      return { valid: false, message: "Mes inválido" };
    }

    // Current month - always valid
    if (selectedMonthIndex === currentMonth) {
      return { valid: true, message: "" };
    }

    // Previous month - valid only in first 15 days of current month
    if (selectedMonthIndex === currentMonth - 1) {
      if (currentDay <= 15) {
        return { valid: true, message: "" };
      } else {
        return { valid: false, message: "Solo se puede guardar el mes anterior hasta el día 15 del mes actual" };
      }
    }

    // Handle year boundary: January (0) to December (11) - previous month
    if (currentMonth === 0 && selectedMonthIndex === 11) {
      if (currentDay <= 15) {
        return { valid: true, message: "" };
      } else {
        return { valid: false, message: "Solo se puede guardar el mes anterior hasta el día 15 del mes actual" };
      }
    }

    // Future months - not valid
    return { valid: false, message: "No se puede guardar meses futuros" };
  }

  describe('Enero - día 10 (primeros 15 días)', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 0, 10); // 10 de enero
    });

    it('debe permitir guardar enero (mes actual)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('debe permitir guardar diciembre (mes anterior, antes del 15)', () => {
      const result = isValidMonthToSave("diciembre", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir guardar febrero (mes futuro)', () => {
      const result = isValidMonthToSave("febrero", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });

    it('no debe permitir guardar marzo (mes futuro)', () => {
      const result = isValidMonthToSave("marzo", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Enero - día 20 (después del 15)', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 0, 20); // 20 de enero
    });

    it('debe permitir guardar enero (mes actual)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir guardar diciembre (mes anterior, después del 15)', () => {
      const result = isValidMonthToSave("diciembre", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("Solo se puede guardar el mes anterior hasta el día 15 del mes actual");
    });

    it('no debe permitir guardar febrero (mes futuro)', () => {
      const result = isValidMonthToSave("febrero", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Diciembre - día 10', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 11, 10); // 10 de diciembre
    });

    it('debe permitir guardar diciembre (mes actual)', () => {
      const result = isValidMonthToSave("diciembre", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('debe permitir guardar noviembre (mes anterior, antes del 15)', () => {
      const result = isValidMonthToSave("noviembre", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir guardar enero (mes futuro)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Caso específico: 01/02/2026 guardando enero', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 1, 1); // 1 de febrero de 2026
    });

    it('SÍ debe permitir guardar enero (mes anterior, antes del día 15)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('debe permitir guardar febrero (mes actual)', () => {
      const result = isValidMonthToSave("febrero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir guardar marzo (mes futuro)', () => {
      const result = isValidMonthToSave("marzo", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Caso específico: 05/02/2026 guardando enero', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 1, 5); // 5 de febrero de 2026
    });

    it('SÍ debe permitir guardar enero (mes anterior, antes del día 15)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('debe permitir guardar febrero (mes actual)', () => {
      const result = isValidMonthToSave("febrero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir guardar marzo (mes futuro)', () => {
      const result = isValidMonthToSave("marzo", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Caso específico: 11/02/2026 guardando marzo', () => {
    let testDate: Date;
    
    beforeEach(() => {
      testDate = new Date(2026, 1, 11); // 11 de febrero de 2026
    });

    it('debe permitir guardar enero (mes anterior, antes del día 15)', () => {
      const result = isValidMonthToSave("enero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('debe permitir guardar febrero (mes actual)', () => {
      const result = isValidMonthToSave("febrero", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('NO debe permitir guardar marzo (mes futuro)', () => {
      const result = isValidMonthToSave("marzo", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("No se puede guardar meses futuros");
    });
  });

  describe('Casos límite', () => {
    it('debe manejar mes inválido', () => {
      const testDate = new Date(2026, 0, 10);
      const result = isValidMonthToSave("mesinvalido", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("Mes inválido");
    });

    it('debe permitir día 15 exactamente', () => {
      const testDate = new Date(2026, 0, 15); // 15 de enero
      const result = isValidMonthToSave("diciembre", testDate);
      expect(result.valid).toBe(true);
      expect(result.message).toBe("");
    });

    it('no debe permitir día 16', () => {
      const testDate = new Date(2026, 0, 16); // 16 de enero
      const result = isValidMonthToSave("diciembre", testDate);
      expect(result.valid).toBe(false);
      expect(result.message).toBe("Solo se puede guardar el mes anterior hasta el día 15 del mes actual");
    });
  });
});