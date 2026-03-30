import { object, string, minLength, regex } from 'valibot';

const nameRegex = /^[\pL\s]+$/u;

export const workerSchema = object({
    name: string([
        minLength(1, 'El nombre es requerido'),
        regex(nameRegex, 'Solo se permiten letras'),
    ]),
    lastname: string([
        minLength(1, 'Los apellidos son requeridos'),
        regex(nameRegex, 'Solo se permiten letras'),
    ]),
    area: string([
        minLength(1, 'El área es requerida'),
    ]),
});

export const initialWorkerData = {
    name: '',
    lastname: '',
    area: '',
};
