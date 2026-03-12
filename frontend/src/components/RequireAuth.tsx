"use client";

import { getToken } from "@/lib/session";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

type RequireAuthProps = {
  children: React.ReactNode;
};

export function RequireAuth({
  children,
}: RequireAuthProps): React.ReactElement {
  const router = useRouter();
  const [allowed, setAllowed] = useState(false);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.replace("/login");
      return;
    }

    // eslint-disable-next-line react-hooks/set-state-in-effect
    setAllowed(true);
  }, [router]);

  if (!allowed) {
    return <div className="screen-center">Checking session...</div>;
  }

  return <>{children}</>;
}
