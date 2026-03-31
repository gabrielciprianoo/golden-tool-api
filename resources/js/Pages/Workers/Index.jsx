import { useState, useCallback, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useWorkerStore } from '../../stores/useWorkerStore';
import { workerSchema } from '../../schemas/workerSchema';
import { safeParse } from 'valibot';
import Modal from '../../Components/Modal';
import Badge from '../../Components/Badge';

export default function WorkersIndex() {
    const { workers, filters } = usePage().props;
    const [search, setSearch] = useState(filters?.search || '');
    const [errors, setErrors] = useState({});

    const {
        isModalOpen,
        editingWorker,
        formData,
        openCreateModal,
        openEditModal,
        closeModal,
        setFormData,
    } = useWorkerStore();

    const handleSearch = useCallback((value) => {
        setSearch(value);
        router.get('/workers', { search: value }, { replace: true });
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters?.search) {
                router.get('/workers', { search }, { replace: true });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    const validateForm = () => {
        const result = safeParse(workerSchema, formData);
        if (result.success) {
            setErrors({});
            return true;
        }
        const fieldErrors = {};
        result.issues.forEach((issue) => {
            const key = issue.path?.[0]?.key;
            if (key) {
                fieldErrors[key] = issue.message;
            }
        });
        setErrors(fieldErrors);
        return false;
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData({ [name]: value });
        if (errors[name]) {
            setErrors((prev) => ({ ...prev, [name]: null }));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        if (editingWorker) {
            router.put(`/workers/${editingWorker.id}`, formData, {
                onSuccess: () => closeModal(),
            });
        } else {
            router.post('/workers', formData, {
                onSuccess: () => closeModal(),
            });
        }
    };

    const deleteWorker = (id) => {
        if (confirm('¿Estás seguro de eliminar este trabajador?')) {
            router.delete(`/workers/${id}`);
        }
    };

    return (
        <div className="min-h-screen bg-gray-100 p-6">
            <div className="max-w-7xl mx-auto">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-800">Trabajadores</h1>
                    <button
                        onClick={openCreateModal}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition-colors"
                    >
                        + Nuevo Trabajador
                    </button>
                </div>

                <div className="mb-4">
                    <input
                        type="text"
                        placeholder="Buscar por nombre, código o área..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Código
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Apellidos
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Área
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {workers.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-4 text-center text-gray-500">
                                        No hay trabajadores registrados
                                    </td>
                                </tr>
                            ) : (
                                workers.map((worker) => (
                                    <tr key={worker.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {worker.worker_code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {worker.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {worker.lastname}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge>{worker.area}</Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <button
                                                onClick={() => openEditModal(worker)}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                onClick={() => deleteWorker(worker.id)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal
                isOpen={isModalOpen}
                onClose={closeModal}
                title={editingWorker ? 'Editar Trabajador' : 'Nuevo Trabajador'}
            >
                <form onSubmit={handleSubmit}>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Nombre
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        {errors.name && (
                            <p className="text-red-500 text-sm mt-1">{errors.name}</p>
                        )}
                    </div>

                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Apellidos
                        </label>
                        <input
                            type="text"
                            name="lastname"
                            value={formData.lastname}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        {errors.lastname && (
                            <p className="text-red-500 text-sm mt-1">{errors.lastname}</p>
                        )}
                    </div>

                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Área
                        </label>
                        <select
                            name="area"
                            value={formData.area}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Seleccionar área</option>
                            <option value="montaje/desmontaje">Montaje/Desmontaje</option>
                            <option value="armado/desarmado">Armado/Desarmado</option>
                        </select>
                        {errors.area && (
                            <p className="text-red-500 text-sm mt-1">{errors.area}</p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={closeModal}
                            className="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            {editingWorker ? 'Actualizar' : 'Crear'}
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
