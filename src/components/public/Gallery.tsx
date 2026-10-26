import Image from "next/image";
import { Camera } from "lucide-react";

const FOTOS = [
  {
    url: "https://images.unsplash.com/photo-1546445317-29f4545e9d53?auto=format&fit=crop&w=800&q=80",
    titulo: "Praderas y Pastoreo Sostenible",
    descripcion: "Nuestras fincas asociadas cuentan con amplias zonas de pastoreo natural.",
  },
  {
    url: "https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?auto=format&fit=crop&w=800&q=80",
    titulo: "Ganado Lechero Seleccionado",
    descripcion: "Cuidado genético y nutricional para producir leche con alto contenido graso.",
  },
  {
    url: "https://images.unsplash.com/photo-1596733430284-f7437764b1a9?auto=format&fit=crop&w=800&q=80",
    titulo: "Control y Acopio Diario",
    descripcion: "Tanques de frío y cantinas desinfectadas bajo estrictas normas de bioseguridad.",
  },
  {
    url: "https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=800&q=80",
    titulo: "Tradición Familiar Campesina",
    descripcion: "Generaciones dedicadas con orgullo a la producción de alimentos de calidad.",
  },
];

export default function Gallery() {
  return (
    <section id="galeria" className="py-20 bg-leche-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-16 space-y-4">
          <span className="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-100 px-3 py-1 rounded-full border border-campo-200">
            Nuestra Tierra y Nuestra Gente
          </span>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-campo-950">
            Galería del Campo y la Asociación
          </h2>
          <p className="text-lg text-gray-700">
            Imágenes de las faenas diarias en las fincas de nuestros asociados, donde nace la leche más fresca y pura de la región.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {FOTOS.map((item, idx) => (
            <div
              key={idx}
              className="bg-white rounded-2xl overflow-hidden shadow-md border border-campo-200 flex flex-col group hover:shadow-xl transition-all duration-300"
            >
              <div className="relative h-56 overflow-hidden bg-campo-900">
                <Image
                  src={item.url}
                  alt={item.titulo}
                  fill
                  sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                  className="object-cover group-hover:scale-105 transition-transform duration-500"
                  loading="lazy"
                />
                <div className="absolute top-3 right-3 bg-white/90 backdrop-blur-sm p-1.5 rounded-full text-campo-800 shadow z-10">
                  <Camera className="w-4 h-4" />
                </div>
              </div>
              <div className="p-5 flex-1 flex flex-col justify-between">
                <div>
                  <h3 className="font-bold text-lg text-campo-950 mb-1.5 group-hover:text-campo-700 transition-colors">
                    {item.titulo}
                  </h3>
                  <p className="text-sm text-gray-600 leading-relaxed">
                    {item.descripcion}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
