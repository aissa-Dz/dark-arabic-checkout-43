import { Button } from "@/components/ui/button";

export const Navbar = () => {
  return (
    <nav className="fixed w-full top-0 z-50 bg-secondary/80 backdrop-blur-sm border-b border-border/50">
      <div className="container mx-auto flex items-center justify-between py-4">
        <div className="text-xl font-bold">الرقمي</div>
        <div className="flex gap-4">
          <Button variant="ghost">المميزات</Button>
          <Button variant="ghost">التسعير</Button>
          <Button variant="ghost">التوثيق</Button>
          <Button className="bg-primary hover:bg-primary/90">ابدأ الآن</Button>
        </div>
      </div>
    </nav>
  );
};