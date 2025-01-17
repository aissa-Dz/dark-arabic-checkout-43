import { Button } from "@/components/ui/button";

export const Hero = () => {
  return (
    <section className="min-h-screen flex items-center justify-center hero-gradient pt-20">
      <div className="container mx-auto text-center">
        <h1 className="text-5xl md:text-7xl font-bold mb-6">
          <span className="gradient-text">إدارة الطلبات</span>
          <br />
          بشكل احترافي
        </h1>
        <p className="text-xl md:text-2xl text-gray-300 mb-8 max-w-2xl mx-auto">
          إضافة ووردبريس متكاملة لإدارة الطلبات وعرض نموذج الدفع عند الاستلام في صفحة المنتج
        </p>
        <div className="flex gap-4 justify-center">
          <Button size="lg" className="bg-primary hover:bg-primary/90 text-lg">
            جرب الإضافة مجاناً
          </Button>
          <Button size="lg" variant="outline" className="text-lg">
            شاهد العرض التوضيحي
          </Button>
        </div>
      </div>
    </section>
  );
};