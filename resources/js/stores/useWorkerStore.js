import { create } from 'zustand';
import { initialWorkerData } from '../schemas/workerSchema';

export const useWorkerStore = create((set) => ({
    isModalOpen: false,
    editingWorker: null,
    formData: { ...initialWorkerData },

    openCreateModal: () => set({
        isModalOpen: true,
        editingWorker: null,
        formData: { ...initialWorkerData },
    }),

    openEditModal: (worker) => set({
        isModalOpen: true,
        editingWorker: worker,
        formData: {
            name: worker.name,
            lastname: worker.lastname,
            area: worker.area,
        },
    }),

    closeModal: () => set({
        isModalOpen: false,
        editingWorker: null,
        formData: { ...initialWorkerData },
    }),

    setFormData: (data) => set((state) => ({
        formData: { ...state.formData, ...data },
    })),

    resetForm: () => set({
        formData: { ...initialWorkerData },
    }),
}));
