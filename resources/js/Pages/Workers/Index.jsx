import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { router, usePage } from '@inertiajs/react';

export default function WorkersIndex() {
    const { workers, filters } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingWorker, setEditingWorker] = useState(null);
    const [search, setSearch] = useState(filters?.search || '');

    const {
        register,
        handleSubmit,
        reset,
        setValue,
        formState: { errors },
    } = useForm();

    const openCreateModal = () => {
        setEditingWorker(null);
        reset();
        setIsModalOpen(true);
    };

    const openEditModal = (worker) => {
        setEditingWorker(worker);
        setValue('name', worker.name);
        setValue('lastname', worker.lastname);
        setValue('area', worker.area);
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingWorker(null);
        reset();
    };

    const onSubmit = (data) => {
        if (editingWorker) {
            router.put(`/workers/${editingWorker.id}`, data, {
                onSuccess: () => closeModal(),
            });
        } else {
            router.post('/workers', data, {
                onSuccess: () => closeModal(),
            });
        }
    };

    const deleteWorker = (id) => {
        if (confirm('¿Estás seguro de eliminar este trabajador?')) {
            router.delete(`/workers/${id}`);
        }
    };

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        router.get('/workers', { search: value }, { replace: true });
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
                        onChange={handleSearch}
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
                                            <span
                                                className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                    worker.area === 'montaje/desmontaje'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-purple-100 text-purple-800'
                                                }`}
                                            >
                                                {worker.area}
                                            </span>
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

            {isModalOpen && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                        <h2 className="text-xl font-bold mb-4">
                            {editingWorker ? 'Editar Trabajador' : 'Nuevo Trabajador'}
                        </h2>
                        <form onSubmit={handleSubmit(onSubmit)}>
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre
                                </label>
                                <input
                                    {...register('name', {
                                        required: 'El nombre es requerido',
                                        pattern: {
                                            value: /^[\pL\s]+$/u,
                                            message: 'Solo se permiten letras',
                                        },
                                    })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.name && (
                                    <p className="text-red-500 text-sm mt-1">{errors.name.message}</p>
                                )}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Apellidos
                                </label>
                                <input
                                    {...register('lastname', {
                                        required: 'Los apellidos son requeridos',
                                        pattern: {
                                            value: /^[\pL\s]+$/u,
                                            message: 'Solo se permiten letras',
                                        },
                                    })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.lastname && (
                                    <p className="text-red-500 text-sm mt-1">{errors.lastname.message}</p>
                                )}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Área
                                </label>
                                <select
                                    {...register('area', { required: 'El área es requerida' })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Seleccionar área</option>
                                    <option value="montaje/desmontaje">Montaje/Desmontaje</option>
                                    <option value="armado/desarmado">Armado/Desarmado</option>
                                </select>
                                {errors.area && (
                                    <p className="text-red-500 text-sm mt-1">{errors.area.message}</p>
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
                    </div>
                </div>
            )}
        </div>
    );
}
