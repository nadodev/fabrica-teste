import calcaImg from '@/assets/prod-calca.jpg';
import escolarImg from '@/assets/prod-escolar.jpg';
import jalecoImg from '@/assets/prod-jaleco.jpg';
import poloImg from '@/assets/prod-polo.jpg';

export type Product = {
    id: string;
    name: string;
    category: string;
    segment: string;
    description: string;
    price: number;
    colors: string[];
    image: string;
    personalizable: boolean;
};

export const products: Product[] = [
    {
        id: 'polo-empresarial',
        name: 'Calça pijama em brim',
        category: 'Calças',
        segment: 'Empresarial',
        description:
            'Peça resistente para rotina operacional, com conforto e ótimo acabamento.',
        price: 69.9,
        colors: ['#003B73', '#1D2939', '#FFFFFF', '#B42318'],
        image: poloImg,
        personalizable: true,
    },
    {
        id: 'calca-profissional',
        name: 'Bata manga longa em brim',
        category: 'Batas',
        segment: 'Profissional',
        description:
            'Proteção, durabilidade e apresentação profissional para equipes.',
        price: 89.9,
        colors: ['#022A50', '#1D2939', '#667085'],
        image: calcaImg,
        personalizable: true,
    },
    {
        id: 'jaleco-branco',
        name: 'Bata manga curta em brim',
        category: 'Batas',
        segment: 'Profissional',
        description: 'Modelo prático para operação, atendimento e produção.',
        price: 119.9,
        colors: ['#FFFFFF'],
        image: jalecoImg,
        personalizable: true,
    },
    {
        id: 'polo-escolar',
        name: 'Camiseta em malha PV',
        category: 'Camisetas',
        segment: 'Empresarial',
        description:
            'Camiseta leve e versátil para equipes, eventos e atendimento.',
        price: 59.9,
        colors: ['#003B73', '#FFFFFF', '#1D2939'],
        image: escolarImg,
        personalizable: true,
    },
    {
        id: 'polo-piquet',
        name: 'Camisa Polo Empresarial',
        category: 'Camisas Polo',
        segment: 'Empresarial',
        description: 'Malha piquet premium com acabamento reforçado.',
        price: 79.9,
        colors: ['#003B73', '#022A50', '#FFFFFF'],
        image: poloImg,
        personalizable: true,
    },
    {
        id: 'calca-brim',
        name: 'Calça profissional em brim',
        category: 'Calças',
        segment: 'Industrial',
        description: 'Brim resistente para uso pesado, com reforço nos bolsos.',
        price: 99.9,
        colors: ['#022A50', '#1D2939'],
        image: calcaImg,
        personalizable: false,
    },
    {
        id: 'jaleco-chef',
        name: 'Dólmã Chef Executivo',
        category: 'Jalecos',
        segment: 'Profissional',
        description: 'Design moderno, botões cobertos e tecido respirável.',
        price: 139.9,
        colors: ['#FFFFFF', '#1D2939'],
        image: jalecoImg,
        personalizable: true,
    },
    {
        id: 'polo-escolar-basica',
        name: 'Uniforme escolar manga curta',
        category: 'Camisas',
        segment: 'Escolar',
        description: 'Tecido leve e resistente a lavagens frequentes.',
        price: 49.9,
        colors: ['#FFFFFF', '#003B73'],
        image: escolarImg,
        personalizable: true,
    },
];

export const formatBRL = (n: number) =>
    n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
