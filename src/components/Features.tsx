import { CheckCircle } from "lucide-react";

export const Features = () => {
  const features = [
    "إدارة الطلبات بواجهة سهلة الاستخدام",
    "نموذج دفع متكامل عند الاستلام",
    "تتبع حالة الطلبات مباشرة",
    "تقارير مفصلة للمبيعات",
    "دعم متعدد العملات",
    "تكامل مع جميع قوالب ووردبريس",
  ];

  return (
    <section className="py-20 bg-secondary">
      <div className="container mx-auto">
        <h2 className="text-4xl font-bold text-center mb-12 gradient-text">
          مميزات الإضافة
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex items-start gap-4 p-6 rounded-lg bg-background/50 backdrop-blur-sm border border-border/50"
            >
              <CheckCircle className="w-6 h-6 text-primary flex-shrink-0" />
              <p className="text-lg">{feature}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};